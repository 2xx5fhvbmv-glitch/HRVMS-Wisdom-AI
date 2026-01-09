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
                </div>
            </div>
            <div class="card card-exitProfilePeopleEmp">
                <div class="row g-xxl-4 g-3 mb-md-4 mb-3">
                    <div class="col-lg-6">
                        <div class="bg-themeGrayLight h-100">
                            <div class="card-title mb-0">
                                <h3>Employee Details</h3>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-lable mb-1">
                                    <tbody>
                                        <tr>
                                            <th>Name:</th>
                                            <td>{{$exit_clearance->employee->resortAdmin->full_name}}</td>
                                        </tr>
                                        <tr>
                                            <th>Employee ID:</th>
                                            <td>{{$exit_clearance->employee->Emp_id}}</td>
                                        </tr>
                                        <tr>
                                            <th>Department:</th>
                                            <td>{{$exit_clearance->employee->department->name}}</td>
                                        </tr>
                                        <tr>
                                            <th>Position:</th>
                                            <td>{{$exit_clearance->employee->position->position_title}}</td>
                                        </tr>
                                       <tr>
                                            <th>Employment Duration:</th>
                                            <td>
                                                {{
                                                    \Carbon\Carbon::parse($exit_clearance->employee->joining_date)->format('d M Y')
                                                    . ' - ' .
                                                    \Carbon\Carbon::parse($exit_clearance->last_working_day)->format('d M Y')
                                                }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="bg-themeGrayLight h-100">
                            <div class="card-title mb-0">
                                <h3>Request Details</h3>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-lable table-reqDetPeopleEmp mb-1">
                                    <tbody>
                                        <tr>
                                            <th>Reason for Resignation:</th>
                                            <td>{{$exit_clearance->reason_title->reason}}</td>
                                        </tr>
                                        <tr>
                                            <th>Last Working Date:</th>
                                            <td>{{ \Carbon\Carbon::parse($exit_clearance->last_working_day)->format('d M Y')}}</td>
                                        </tr>
                                        <tr>
                                            <th>Notice Period:</th>
                                            <td>{{
                                                    \Carbon\Carbon::parse($exit_clearance->resignation_date)->format('d M Y')
                                                    . ' - ' .
                                                    \Carbon\Carbon::parse($exit_clearance->last_working_day)->format('d M Y')
                                                }}</td>
                                        </tr>
                                        <tr>
                                            <th>Required Immediate Release:</th>
                                            <td>{{$exit_clearance->immediate_release}}</td>
                                        </tr>
                                        <tr>
                                            <th>Additional Details:</th>
                                            <td>{{$exit_clearance->comments}}</td>
                                        </tr>
                                        <tr>
                                            <th>Attachments:</th>
                                            <td>
                                                <img src="assets/images/pdf1.svg" alt="icon"
                                                    class="me-2">lorem-ipsum.pdf
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-themeGrayLight mb-md-4 mb-3 @if(App\Helpers\Common::checkRouteWisePermission('people.exit-clearance',config('settings.resort_permissions.edit')) == false) d-none @endif">
                    <div class="card-title mb-0">
                        <div class="d-flex justify-content-between align-items-center">
                            <h3>Exit Interview Status</h3>
                            <a class="btn btn-themeSkyblue" href="{{route('people.exit-clearance.employeeFormAssignment', base64_encode($exit_clearance->id))}}">Assign Employee Form</a>
                        </div>
                    </div>
                    @foreach($exitClearanceFormAssignments as $exitClearanceFormAssignment)
                        <div class="row g-xxl-4 g-md-3 g-2">
                            <div class="col-lg-6">
                                <div class="table-responsive">
                                    <table class="table table-lable mb-1">
                                        <tbody>
                                            <tr>
                                                <th>Form Assigned:</th>
                                                <td>Yes
                                                    @if($is_hr == true )
                                                    <a href="{{route('people.exit-clearance.employeeFormAssignmentShow',base64_encode($exitClearanceFormAssignment->id))}}" class="btn-lg-icon icon-bg-yellow mx-1"><i
                                                            class="fa-solid fa-link"></i></a>
                                                    @endif
                                                    <span class="">{{ $exitClearanceFormAssignment->exitClearanceForm->form_name }}</span></td>
                                            </tr>
                                            <tr>
                                                <th>Date Assigned:</th>
                                                <td>{{ Carbon\Carbon::parse($exitClearanceFormAssignment->assigned_date)->format('d M Y') }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="table-responsive">
                                    <table class="table table-lable mb-1">
                                        <tbody>
                                            <tr>
                                                <th>Response Deadline:</th>
                                                <td> {{ Carbon\Carbon::parse($exitClearanceFormAssignment->deadline_date)->format('d M Y') }} </td>
                                            </tr>
                                            <tr>
                                                <th>Completion Status:</th>
                                                <td>{{ $exitClearanceFormAssignment->status }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="bg-themeGrayLight mb-md-4 mb-3">
                    <div class="card-title">
                        <h3>Departure Arrangements</h3>
                    </div>
                    <div class="row g-xxl-4 g-md-3 g-2">
                        <div class="col-xl-5 col-md-6">
                            <div class="form-check mb-md-4 mb-2">
                                    <input class="form-check-input dep-arrangement-checkbox" type="checkbox" id="international_flight" value="option1" @if(isset($exit_clearance->departure_arrangements) && $exit_clearance->departure_arrangements['international_flight'] == 1) checked @endif>
                                    <label class="form-check-label" for="international_flight">Has the international flight
                                        ticket been booked?</label>
                            </div>
                            <div class="form-check  mb-md-4 mb-2">
                                    <input class="form-check-input dep-arrangement-checkbox" type="checkbox" id="transportation_arranged" value="option2" @if(isset($exit_clearance->departure_arrangements) && $exit_clearance->departure_arrangements['transportation_arranged'] == 1) checked @endif>
                                    <label class="form-check-label" for="transportation_arranged">Has transportation been
                                        arranged?</label>
                            </div>
                            <div class="form-check">
                                    <input class="form-check-input dep-arrangement-checkbox" type="checkbox" id="passport_validity" value="option3" @if(isset($exit_clearance->departure_arrangements) && $exit_clearance->departure_arrangements['passport_validity'] == 1) checked @endif>
                                    <label class="form-check-label" for="passport_validity">Has the employee’s passport validity
                                        been verified? <span>(Passport Validity: 14 April 2025 and Visa Validity : 14 April
                                        2025)</span></label>
                            </div>
                        </div>
                        <div class="col-xl-7 col-md-6">
                            <div class="form-check  mb-md-4 mb-2">
                                    <input class="form-check-input dep-arrangement-checkbox" type="checkbox" id="accommodation_arranged" value="option4" @if(isset($exit_clearance->departure_arrangements) && $exit_clearance->departure_arrangements['accommodation_arranged'] == 1) checked @endif>
                                    <label class="form-check-label" for="accommodation_arranged">Has accommodation in Malé been
                                        arranged?</label>
                            </div>
                            <div class="form-check">
                                    <input class="form-check-input dep-arrangement-checkbox" type="checkbox" id="documentVerifed" value="option5" @if(isset($exit_clearance->departure_arrangements) && $exit_clearance->departure_arrangements['documentVerifed'] == 1) checked @endif>
                                    <label class="form-check-label" for="documentVerifed">Has the employee’s visa documentation
                                        been verified and cleared?</label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-footer">
                    <div class="row align-items-center g-2">
                        <div class="col-auto">
                            <a href="javascript:void(0)" onclick="sendEmploymentCertificate()" class="a-link">Send An Employment Certificate</a>
                        </div>
                        <div class="col-auto"><a href="javascript:void()" data-url="{{ route('people.exit-clearance.sendReminder',base64_encode($exit_clearance->id)) }}" class="a-linkTheme " id="send-reminder-btn">Send Reminder To Employee</a></div>
                        
                            @if($is_hr == false && $is_assigned == true)
                                <div class="col-auto ms-auto"> <a href="{{route('people.exit-clearance.department-form',base64_encode($exit_clearance->id))}}" class="btn btn-themeSkyblue btn-sm">Clearance Form</a></div>
                            @elseif($is_hr == true && $is_assigned == true)
                                <div class="col-auto ms-auto"> <a href="{{route('people.exit-clearance.department-form',base64_encode($exit_clearance->id))}}" class="btn btn-themeSkyblue btn-sm">Clearance Form</a></div>
                                <div class="col-auto"><a href="{{route('payroll.final.settlement')}}" class="btn  btn-themeBlue btn-sm">Full And Final
                                                            Settlement</a></div>
                                <div class="col-auto"><a href="{{route('people.exit-clearance.markAsComplete',$exit_clearance->id)}}" class="btn  btn-themeGreenNew btn-sm">Mark As Completed</a>
                                </div>
                            @elseif($is_hr == true && $is_assigned == false)
                                <div class="col-auto ms-auto"><a href="{{route('payroll.final.settlement')}}" class="btn  btn-themeBlue btn-sm">Full And Final
                                                            Settlement</a></div>
                                <div class="col-auto"><a href="{{route('people.exit-clearance.markAsComplete',$exit_clearance->id)}}" class="btn  btn-themeGreenNew btn-sm">Mark As Completed</a>
                                </div>

                            @endif
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
    $(document).ready(function(){
        
        getExitClearanceData();

        $('.select2t-none').select2();

        $('.datepicker').datepicker({
            format: 'dd/mm/yyyy',
            autoclose: true
        });

        $('#deptFilter, #positionFilter, #statusFilter, #datapicker').on('change', function () {
            getExitClearanceData();
        });
    
    });

    $('#send-reminder-btn').on('click', function (e) {
        e.preventDefault();
        var url = $(this).data('url');
        $.ajax({
            url: url,
            type: 'GET',
            success: function (response) {
                if (response.success) {
                    toastr.success(response.message, "Success", {
                        positionClass: 'toast-bottom-right'
                    });
                } else {
                    toastr.error(response.message , "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                }
            },
            error: function (xhr) {
                toastr.error(xhr.responseJSON.message || 'Something went wrong!', "Error", {
                    positionClass: 'toast-bottom-right'
                });
            }
        });
    });


    function getExitClearanceData() {
        if ($.fn.dataTable.isDataTable('#exit-clearance-table')) {
            $('#exit-clearance-table').DataTable().destroy();
        }
        $table = $('#exit-clearance-table').DataTable({
            "searching": false,
            "bLengthChange": false,
            "bFilter": true,
            "bInfo": true,
            "bAutoWidth": false,
            "scrollX": true,
            "iDisplayLength": 10,
            processing: true,
            serverSide: true,
            order:[[8, 'desc']],
            ajax: {
                url: "{{ route('people.exit-clearance') }}",
                type: 'GET',
                data: function (d) {
                    d.department_id = $('#deptFilter').val();
                    d.position_id = $('#positionFilter').val();
                    d.status = $('#statusFilter').val();
                    d.date_range = $('#datapicker').val();
                }
            },
            columns: [
                { data: 'Emp_id', name: 'Emp_id' },
                { data: 'employee_name', name: 'employee_name' },
                { data: 'position', name: 'position' },
                { data: 'department', name: 'department' },
                { data: 'resignation_date', name: 'resignation_date' },
                { data: 'last_working_day', name: 'last_working_day' },
                { data: 'status', name: 'status' },
                { data: 'action', name: 'action', orderable: false, searchable: false },
                {data:'created_at',visible:false,searchable:false},
            ]
        });
    }
    
</script>
<script>
$(document).on('change', '.dep-arrangement-checkbox', function() {
    let arrangementData = {};
    $('.dep-arrangement-checkbox').each(function() {
        arrangementData[$(this).attr('id')] = $(this).is(':checked') ? 1 : 0;
    });
    $.ajax({
        url: "{{ route('people.exit-clearance.employeeDepartureArrangement', base64_encode($exit_clearance->id)) }}",
        method: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            arrangements: arrangementData
        },
        success: function(response) {
            if(response.status === 'success') {
                $('#departure-arrangements').html(response.html);
            }
        }
    });
});

function sendEmploymentCertificate() {
    $.ajax({
        url: "{{ route('people.exit-clearance.employement-certificate', base64_encode($exit_clearance->id)) }}",
        type: "GET",
        success: function(response) {
            if(response.success) {
                toastr.success(response.message, "Success", {
                    positionClass: 'toast-bottom-right'
                });
            }else{
                toastr.error(response.message || 'Something went wrong!', "Error", {
                    positionClass: 'toast-bottom-right'
                });
            }
        },
        error: function(xhr) {
            toastr.error(xhr.responseJSON.message || 'Something went wrong!');
        }
    });
}
</script>
@endsection
