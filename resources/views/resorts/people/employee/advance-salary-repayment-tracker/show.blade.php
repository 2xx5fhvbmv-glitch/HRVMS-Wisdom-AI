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
                    <div class="col-auto  ms-auto"><a class="btn btn-theme" href="{{route('people.advance-salary-repayment-tracker.downloadPdf',base64_encode($payrollAdvance->id))}}">Download Repayment Report</a></div>
                </div>
            </div>
            <div class="card card-repayTrackEmpDetailPeopleEmp">
                <div class="card-title">
                    <div class="row g-md-2 g-1">
                        <div class="col-auto">
                            <h3 class="text-nowrap">Employee Repayment Detail</h3>
                        </div>
                        @if($payrollAdvance->recovery_status == 'Scheduled')
                            <div class="col-auto ms-auto">
                                <a href="{{route('people.advance-salary-repayment-tracker.markAsComplete',base64_encode($payrollAdvance->id))}}" class="btn btn-themeBlueBr btn-xs">Mark As Completed</a>
                            </div>
                        @elseif($payrollAdvance->recovery_status == 'Completed')
                            <div class="col-auto ms-auto">
                                <span class="badge badge-themeSuccess btn-xs">Completed</span>
                            </div>
                        @else
                             <div class="col-auto ms-auto"></div>
                        @endif

                        <div class="col-auto">
                            <a href="#" class="btn btn-themeBlueBr btn-xs">Resend Notification</a>
                        </div>
                    </div>
                </div>
                <div class="bg-themeGrayLight mb-md-4 mb-3">
                    <div class="card-title mb-0">
                        <h3>Original Request Details</h3>
                    </div>
                    <div class="row g-xxl-4 g-md-3 g-2">
                        <div class="col-lg-6">
                            <div class="table-responsive">
                                <table class="table table-lable  table-oriReqDetailPeopleEmp mb-1">
                                    <tbody>
                                        <tr>
                                            <th>Employee Name:</th>
                                            <td>
                                                <div class="tableUser-block">
                                                    <div class="img-circle"><img src="{{App\Helpers\Common::getResortUserPicture($payrollAdvance->employee->Admin_Parent_id)}}"
                                                            alt="user">
                                                    </div>
                                                    <span class="userApplicants-btn">{{$payrollAdvance->employee->resortAdmin->full_name}}</span>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Employee ID:</th>
                                            <td>{{$payrollAdvance->employee->Emp_id}}</td>
                                        </tr>
                                        <tr>
                                            <th>Department:</th>
                                            <td>{{$payrollAdvance->employee->department->name}}</td>
                                        </tr>
                                        <tr>
                                            <th>Position:</th>
                                            <td>{{$payrollAdvance->employee->position->position_title}}</td>
                                        </tr>
                                        <tr>
                                            <th>Purpose:</th>
                                            <td>{{$payrollAdvance->pourpose}}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="table-responsive">
                                <table class="table table-lable table-oriReqDetailPeopleEmpSec mb-1">
                                    <tbody>
                                        <tr>
                                            <th>Original Request Date:</th>
                                            <td>{{$payrollAdvance->request_date}}</td>
                                        </tr>
                                        <tr>
                                            <th>Requested Amount:</th>
                                            <td>{{$payrollAdvance->request_amount}}</td>
                                        </tr>
                                        <tr>
                                            <th>HR Approver:</th>
                                            <td>
                                                <div class="tableUser-block">
                                                    <div class="img-circle"><img src="{{App\Helpers\Common::getResortUserPicture($payrollAdvance->hrApprover->Admin_Parent_id)}}"
                                                            alt="user">
                                                    </div>
                                                    <span class="userApplicants-btn">{{$payrollAdvance->hrApprover->resortAdmin->full_name}}</span>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>HR Approval Date:</th>
                                            <td>{{Carbon\Carbon::parse($payrollAdvance->hr_action_date)->format('d M Y') ?? ''}}</td>
                                        </tr>
                                        @if($payrollAdvance->financeApprover)
                                        <tr>
                                            <th>Finance Approver:</th>
                                            <td>
                                                <div class="tableUser-block">
                                                    <div class="img-circle"><img src="{{App\Helpers\Common::getResortUserPicture($payrollAdvance->financeApprover->Admin_Parent_id)}}"
                                                            alt="user">
                                                    </div>
                                                    <span class="userApplicants-btn">{{$payrollAdvance->financeApprover->resortAdmin->full_name}}</span>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Finance Approver Date:</th>
                                            <td>{{Carbon\Carbon::parse($payrollAdvance->finance_action_date)->format('d M Y') ?? ''}}</td>
                                        </tr>
                                        @endif
                                        @if($payrollAdvance->gmApprover)
                                        <tr>
                                            <th>GM Approver:</th>
                                            <td>
                                                <div class="tableUser-block">
                                                    <div class="img-circle"><img src="{{App\Helpers\Common::getResortUserPicture($payrollAdvance->gmApprover->Admin_Parent_id)}}"
                                                            alt="user">
                                                    </div>
                                                    <span class="userApplicants-btn">{{$payrollAdvance->gmApprover->resortAdmin->full_name}}</span>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>GM Approver Date:</th>
                                            <td>{{Carbon\Carbon::parse($payrollAdvance->gm_action_date)->format('d M Y') ?? ''}}</td>
                                        </tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-themeGrayLight mb-md-4 mb-3">
                    <div class="card-title mb-0">
                        <h3>Repayment Schedule</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-lable table-repaySchePeopleEmp mb-1">
                             <thead>
                                <tr>
                                    <th>Month/Date</th>
                                    <th>Schedule Amount</th>
                                    <th>Remark</th>
                                    <th>Action</th>
                                </tr>
                            </thead> 
                            <tbody>

                               @foreach($payrollAdvance->payrollRecoverySchedule as $schedule)
                                
                                <tr>
                                    <td>{{Carbon\Carbon::parse($schedule->repayment_date)->format('F Y')}}</td>
                                    <td>{{$schedule->amount}}</td>
                                    <td>{{$schedule->remark ?? ''}}</td>
                                    <td><a href="javascript:void(0);" class="a-link me-md-2 me-1 edit-row-btn @if(Common::checkRouteWisePermission('people.advance-salary-repayment-tracker.index',config('settings.resort_permissions.edit')) == false) d-none @endif" data-id="{{$schedule->id}}">Edit</a>
                                        <a href="javascript:void(0);" class="a-linkTheme @if(Common::checkRouteWisePermission('people.advance-salary-repayment-tracker.index',config('settings.resort_permissions.edit')) == false) d-none @endif" id="addNote">Add Note</a>
                                    </td>
                                </tr>
                               @endforeach
                              
                                <tr>
                                    <th>Total</th>
                                    <th>${{$payrollAdvance->payrollRecoverySchedule->sum('amount')}}</th>
                                    <th></th>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                {{-- Hold for payroll deduction  --}}
                <div class="bg-themeGrayLight mb-md-4 mb-3">
                    <div class="card-title mb-0">
                        <h3>Deduction History</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-lable table-dedHisPeopleEmp mb-1">
                            <tbody>
                                <tr>
                                    <th>Payroll Month</th>
                                    <th>Deducted Amount</th>
                                    <th>Payroll Cycle</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                                @foreach($payrollAdvance->payrollRecoverySchedule as $schedule)
                                <tr>
                                    <td>{{Carbon\Carbon::parse($schedule->repayment_date)->format('F Y')}}</td>
                                    <td>{{$schedule->amount}}</td>
                                    <td>{{Carbon\Carbon::parse($schedule->repayment_date)->addMonth()->format('F Y')}}</td>
                                    <td>
                                        @if($schedule->status == 'Paid')
                                            <span class="badge badge-themeSuccess">Completed</span>
                                        @elseif($schedule->status == 'Pending')
                                            <span class="badge badge-themeWarning">Pending</span>

                                        @endif
                                    </td>
                                    <td>
                                        @if($schedule->status == 'Paid')
                                            <a href="#" class="a-link">View Receipt</a>
                                        @else
                                            <span class="badge badge-themeDanger">No Receipt</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                                
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>


<div class="modal fade" id="addNote-modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-addNote">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Note</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="{{route('people.advance-salary-repayment-tracker.addNotes')}}" id="addNoteForm" method="POST">
                     @csrf
                     <input type="hidden" name="schedule_id" value="">
                    <textarea id="remark" class="form-control" name="remark" rows="3" placeholder="write your Remark" required></textarea>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-themeDanger" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" id="submitBtn" class="btn btn-themeBlue ">Submit</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('import-css')
@endsection

@section('import-scripts')
<script>  

$(document).on("click", ".edit-row-btn", function(event) {
        event.preventDefault(); // Prevent default action

        var $row = $(this).closest("tr");
        var scheduleId = $(this).data("id");
        var repaymentDate = $row.find("td:nth-child(1)").text().trim();
        var amount = $row.find("td:nth-child(2)").text().trim();


        var editRowHtml = `
            <td>
                <select class="form-control repayment-date">
                    @foreach($availableMonths as $month)
                        <option value="{{ $month }}" >{{ $month }}</option>
                    @endforeach
                </select>
            </td>
            <td>
                <input type="text" class="form-control amount" value="${amount}" />
            </td>
            
            <td >
                <a href="javascript:void(0)" class="btn btn-theme update-row-btn" data-id="${scheduleId}">Submit</a>
            </td>
        `;

        $row.html(editRowHtml);
    });

    $(document).on("click", ".update-row-btn", function(event) {
        event.preventDefault();

        var $row = $(this).closest("tr");
        var scheduleId = $(this).data("id");
        var repaymentDate = $row.find(".repayment-date").val().trim();
        var amount = $row.find(".amount").val().trim();

        // Perform AJAX request to update the data
        $.ajax({
            url: "{{ route('people.advance-salary-repayment-tracker.update')}}",
            type: "POST",
            data: {
                schedule_id: scheduleId,
                repayment_date: repaymentDate,
                amount: amount,
                _token: "{{ csrf_token() }}"
            },
            success: function(response) {
                if (response.success) {
                    $row.html(`
                        <td>${repaymentDate}</td>
                        <td>${amount}</td>
                        <td>
                            <a href="javascript:void(0);" class="a-link me-md-2 me-1 edit-row-btn" data-id="${scheduleId}">Edit</a>
                            <a href="#" class="a-linkTheme">Add Note</a>
                        </td>
                    `);
                    toastr.success(response.message, "Success", {
                        positionClass: 'toast-bottom-right'
                    });
                }else{
                    toastr.error(response.message, "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                }
            },
            error: function() {
                toastr.error("Something went wrong while updating the repayment schedule.", "Error", {
                    positionClass: 'toast-bottom-right'
                });
            }
        });
    });

    $(document).on("click", "#addNote", function(event) {
        event.preventDefault();
        $("#addNoteForm input[name='schedule_id']").val($(this).closest('tr').find('.edit-row-btn').data('id'));
        $('#addNote-modal').modal('show');
    });

$(document).on("click", "#submitBtn", function(event) {
    event.preventDefault();
    var form = $('#addNoteForm');
    var formData = form.serialize();
    $.ajax({
        url: form.attr('action'),
        type: 'POST',
        data: formData,
        success: function(response) {
            if(response.success) {
                $('#addNote-modal').modal('hide');
                toastr.success(response.message , "Success", {
                    positionClass: 'toast-bottom-right'
                });
            } else {
                toastr.error(response.message , "Error", {
                    positionClass: 'toast-bottom-right'
                });
            }
        },
        error: function(xhr) {
            toastr.error('An error occurred while adding the note.', "Error", {
                positionClass: 'toast-bottom-right'
            });
        }
    });
});

</script>
@endsection

