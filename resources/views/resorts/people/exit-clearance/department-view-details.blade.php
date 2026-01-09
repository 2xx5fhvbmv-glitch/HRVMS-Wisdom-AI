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
                <div class="bg-themeGrayLight mb-md-4 mb-3">
                    <div class="card-title mb-0">
                        <div class="">
                            <h3>Exit Interview Status</h3>
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
                
                <div class="card-footer">
                    <div class="row g-2 d-flex justify-content-end align-items-end">
                              <div class="col-auto "> <a href="{{route('people.exit-clearance.department-form',base64_encode($exit_clearance->id))}}" class="btn btn-themeSkyblue btn-sm">Clearance Form</a></div>
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
</script>
@endsection
