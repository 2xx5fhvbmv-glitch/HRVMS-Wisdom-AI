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
                <div class="row justify-content-between g-3">
                    <div class="col-auto">
                        <div class="page-title">
                            <span>Performance</span>
                            <h1>{{ $page_title }}</h1>
                        </div>
                    </div>
                    <div class="col-auto">
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('Performance.MonltyCheckIn.history') }}" class="btn perf-btn-secondary">View All</a>
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <div class="card">
                <form id="MonthlyCheckinForm" data-parsley-validate>
                     @csrf
                        <div class="row g-md-4 g-3 mb-md-4 mb-3">
                            <div class="col-xl-4 col-md-5">
                                <div class="bg-themeGrayLight sumDisEmp-block">
                                    <div class="card-title mb-md-3">
                                        <h3>Select Employee</h3>
                                    </div>
                                    <input type="search" class="form-control mb-3 search" placeholder="Search">
                                    <div class="overflow-auto pe-1 AppendEmp_id">
                                        @if($Employee->isNotEmpty())
                                        @foreach($Employee as $e)
                                        <div class="d-flex  Employee" data-id="{{$e->emp_id}}" 
                                            data-profile="{{$e->profileImg}}"
                                            data-position="{{$e->PositionName}}"
                                            data-position="{{$e->PositionName}}"
                                            data-first_name="{{$e->first_name}}"
                                            data-last_name="{{$e->last_name}}"
                                            data-Emp_id="{{$e->Emp_id}}"
                                            >
                                            <div class="img-circle userImg-block "><img src="{{$e->profileImg}}"
                                                    alt="user">
                                            </div>
                                            <div>
                                                <h6>{{$e->first_name}}  {{$e->last_name}}</h6>
                                                <p>{{$e->PositionName}}</p>
                                            </div>
                                        </div>
                                        @endforeach
                                        @endif
                                    
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-8 col-md-7 sumDisEmpDetail-block">
                                <div class="d-flex align-items-center mb-md-4 pb-md-1 mb-3" id="Append_id">
                                
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mt-3">
                                        <label for="date_discussion" class="form-label">DATE OF DISCUSSION <span class="text-danger">*</span></label>
                                        <input type="text" id="date_discussion"
                                            required
                                            data-parsley-required-message="Date of discussion is required"
                                            name="date_discussion" class="form-control datepicker" placeholder="Select Date">
                                    </div>

                                    <div class="col-md-6 mt-3">
                                        <label for="Time" class="form-label">START TIME <span class="text-danger">*</span></label>
                                        <input type="time" id="Time" name="start_time" 
                                            required
                                            data-parsley-required-message="Time is required"
                                            class="form-control" placeholder="Select Start Time">
                                    </div>
                                    <div class="col-md-6 mt-3">
                                        <label for="Time" class="form-label">END TIME <span class="text-danger">*</span></label>
                                        <input type="time" id="Time" name="end_time" 
                                            required
                                            data-parsley-required-message="Time is required"
                                            class="form-control" placeholder="Select End Time">
                                    </div>
                                    
                                    <div class="col-md-6 mt-3">
                                        <label class="form-label">Meeting Place <span class="text-danger">*</span></label>
                                        <input type="text" name="Meeting_Place"
                                            required
                                            data-parsley-required-message="Meeting Place is required"
                                            class="form-control" placeholder="Enter Meeting Place">
                                    </div>

                                    <div id="stage2Fields" class="d-none w-100">
                                        <div class="alert alert-info py-2 mx-2 my-2" id="stage2Banner">
                                            Employee approved this request — please fill the remaining details and submit.
                                        </div>

                                        <div class="col-md-6 mt-3">
                                            <label class="form-label">AREA OF DISCUSSION <span class="text-danger">*</span></label>
                                            <input type="text" name="Area_of_Discussion"
                                                class="form-control" placeholder="Enter Area">
                                        </div>

                                        <div class="col-md-6 mt-3">
                                            <label class="form-label">AREA OF IMPROVEMENT <span class="text-danger">*</span></label>
                                            <input type="text" name="Area_of_Improvement"
                                                class="form-control" placeholder="Enter Improvement Area">
                                        </div>

                                        <div class="col-md-6 mt-3">
                                            <label class="form-label">TIME LINE <span class="text-danger">*</span></label>
                                            <input type="text" name="Time_Line"
                                                class="form-control" placeholder="Enter Timeline">
                                        </div>

                                        <div class="mb-md-4 mt-3 mb-3 pb-2">
                                            <label for="summary" class="form-label">COMMENT <span class="text-danger">*</span></label>
                                            <textarea id="summary" name="comment" class="form-control" rows="4" placeholder="Type Here..."></textarea>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-2 mt-4 mb-3 pb-2">
                                                <a href="javascript:void(0)" class="AddTraning btn perf-btn-accent btn-sm">Add Training</a>
                                            </div>
                                            <div class="col-md-5 checkTraning d-none">
                                                <label for="select_training" class="form-label">SELECT TRAINING</label>
                                                <select class="form-select" id="select_training" name="tranining_id">
                                                    <option value="">Select Training</option>
                                                    @if(isset($learningProgram) && $learningProgram->isNotempty())
                                                        @foreach($learningProgram as $l)
                                                            <option value="{{$l->id}}">{{$l->name}}</option>
                                                        @endforeach
                                                    @endif
                                                </select>
                                            </div>
                                            <div class="col-md-5 checkTraning d-none">
                                                <label for="select_learning_manager_id" class="form-label">SELECT LEARNING MANAGER</label>
                                                <select class="form-select" id="select_learning_manager_id" name="learning_manager_id">
                                                    <option value="">Select Learning Manager</option>
                                                    @if(isset($learningManagers) && $learningManagers->isNotempty())
                                                        @foreach($learningManagers as $l)
                                                            <option value="{{$l->id}}">{{$l->resortAdmin->first_name}}  {{$l->resortAdmin->last_name}}  ({{$l->position->position_title}})</option>
                                                        @endforeach
                                                    @endif
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            
                            </div>
                        </div>
                        <input type="hidden" name="emp_id" id="emp_hidden_id">
                        <input type="hidden" name="checkin_id" id="checkin_hidden_id">

                        <div class="card-footer text-end">
                            <button type='button' id="sendApprovalBtn" class="btn perf-btn-primary btn-sm">Send for Approval</button>
                            <button type='button' id="submitFinalBtn" class="btn perf-btn-primary btn-sm d-none">Submit Check-In</button>
                            <button type='button' id="cancelStage2Btn" class="btn perf-btn-neutral btn-sm d-none">Cancel</button>
                        </div>
                    </form>
                </div>

                <div class="card mt-3">
                    <div class="card-header">
                        <h5 class="mb-0">Approved Check-Ins (awaiting final submission)</h5>
                    </div>
                    <div class="card-body">
                        <div id="approvedListContainer" class="row g-3">
                            <div class="col-12 text-center text-muted py-3">Loading…</div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
@include('resorts.Performance._performance_buttons_v2_styles')
@endsection

@section('import-css')
@endsection

@section('import-scripts')
<script>
$(document).ready(function(){
    $('#MonthlyCheckinForm').parsley();

    $("#select_training").select2({
        placeholder:"select Tranining"
    });
    $("#date_discussion").datepicker({
        format: 'dd/mm/yyyy',
        autoclose: true,
        todayHighlight: true
    });
})
$(document).on("click",".Employee",function(){
    $('.Employee').removeClass('select'); // clear active from all
    $(this).addClass('select');  

    var id  = $(this).data('id');
    var profile =   $(this).data('profile');
    var position =   $(this).data('position');
    var first_name =   $(this).data('first_name');
    var last_name =   $(this).data('last_name');
    var Emp_id =   $(this).data('emp_id');
    $("#emp_hidden_id").val(Emp_id);
    $("#Append_id").html(`<div class="img-circle userImg-block me-lg-4 me-md-3 me-2">
                            <img id="profileImg"   src="${profile}" alt="user">
                        </div>
                        <div>
                            <h4 class="mb-md-2 mb-1">${first_name} ${last_name}<span class="badge badge-themeNew">${Emp_id}</span>
                            </h4>
                            <p>${position}</p>
                        </div>`);
});
$(document).on("click",".AddTraning",function(){
    $(".checkTraning").toggleClass("d-none");
});

$(document).on("keyup",".search",function(){

    $.ajax({
                url: "{{ route('Performance.GetEmployeeDetailsMonthlyChecking') }}", 
                type: 'get',
                data: {"_token":"{{csrf_token()}}","search":$(this).val()},
                success: function(response) {
                    if (response.success)
                    {
                        $(".AppendEmp_id").html(response.data);
                    } 
                    else
                    {
                            toastr.error(response.message, "Error",
                            {
                                positionClass: 'toast-bottom-right'
                            });
                    }
                },
                error: function(response) 
                {
                    var errors = response.responseJSON;

                    if (errors.error) { 
                        // If it's a duplicate entry error
                        toastr.error(errors.error, "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    } else {
                        // If it's a validation error
                        var errs = '';
                        $.each(errors.errors, function(key, error) {
                            errs += error + '<br>';
                        });

                        toastr.error(errs, "Validation Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                }

            });

});


function showError(response) {
    var errors = response.responseJSON;
    if (errors && errors.message) {
        toastr.error(errors.message, "Error", { positionClass: 'toast-bottom-right' });
    } else if (errors && errors.errors) {
        var errs = '';
        $.each(errors.errors, function(key, error) { errs += error + '<br>'; });
        toastr.error(errs, "Validation Error", { positionClass: 'toast-bottom-right' });
    } else {
        toastr.error('Something went wrong', "Error", { positionClass: 'toast-bottom-right' });
    }
}

function resetToStage1() {
    $('#MonthlyCheckinForm')[0].reset();
    $('#emp_hidden_id').val('');
    $('#checkin_hidden_id').val('');
    $('#Append_id').html('');
    $('.Employee').removeClass('select');
    $('#stage2Fields').addClass('d-none');
    $('#sendApprovalBtn').removeClass('d-none');
    $('#submitFinalBtn').addClass('d-none');
    $('#cancelStage2Btn').addClass('d-none');
    $('[name="date_discussion"], [name="start_time"], [name="end_time"], [name="Meeting_Place"]').prop('readonly', false);
}

// Stage 1 — send approval request
$(document).on("click", "#sendApprovalBtn", function() {
    let empId = $("#emp_hidden_id").val();
    if (!empId) {
        toastr.error("Please select an employee", "Error", { positionClass: 'toast-bottom-right' });
        return;
    }
    let formData = {
        _token: '{{ csrf_token() }}',
        emp_id: empId,
        date_discussion: $('[name="date_discussion"]').val(),
        start_time: $('[name="start_time"]').val(),
        end_time: $('[name="end_time"]').val(),
        Meeting_Place: $('[name="Meeting_Place"]').val(),
    };
    $.ajax({
        url: "{{ route('Performance.MonltyCheckIn.scheduleRequest') }}",
        type: 'POST',
        data: formData,
        success: function(res) {
            if (res.success) {
                toastr.success(res.message, "Success", { positionClass: 'toast-bottom-right' });
                resetToStage1();
                loadApprovedList();
            }
        },
        error: showError
    });
});

// Stage 2 — finalize a previously approved check-in
$(document).on("click", "#submitFinalBtn", function() {
    let checkinId = $('#checkin_hidden_id').val();
    if (!checkinId) {
        toastr.error("No approved check-in selected", "Error", { positionClass: 'toast-bottom-right' });
        return;
    }
    let formData = {
        _token: '{{ csrf_token() }}',
        Area_of_Discussion: $('[name="Area_of_Discussion"]').val(),
        Area_of_Improvement: $('[name="Area_of_Improvement"]').val(),
        Time_Line: $('[name="Time_Line"]').val(),
        comment: $('[name="comment"]').val(),
        tranining_id: $('[name="tranining_id"]').val(),
        learning_manager_id: $('[name="learning_manager_id"]').val(),
    };
    $.ajax({
        url: "{{ url('resort/performance/monthly-check-in/finalize') }}/" + checkinId,
        type: 'POST',
        data: formData,
        success: function(res) {
            if (res.success) {
                toastr.success(res.message, "Success", { positionClass: 'toast-bottom-right' });
                setTimeout(() => { window.location.href = res.route; }, 600);
            }
        },
        error: showError
    });
});

$(document).on("click", "#cancelStage2Btn", function() {
    resetToStage1();
    loadApprovedList();
});

// Load approved check-ins awaiting finalization
function loadApprovedList() {
    $.ajax({
        url: "{{ route('Performance.MonltyCheckIn.approvedList') }}",
        type: 'GET',
        success: function(res) {
            if (!res.success || !res.data.length) {
                $('#approvedListContainer').html('<div class="col-12 text-center text-muted py-3">No approved check-ins awaiting submission.</div>');
                return;
            }
            let html = '';
            res.data.forEach(function(row) {
                html += `<div class="col-xl-4 col-md-6">
                    <div class="empListPeopleEmp-block p-3 approved-checkin" data-row='${JSON.stringify(row)}' style="cursor:pointer;">
                        <div class="d-flex align-items-center gap-2">
                            <div class="img-circle" style="width:48px;height:48px;"><img src="${row.emp_photo}" style="width:48px;height:48px;border-radius:50%;"></div>
                            <div class="flex-grow-1">
                                <h6 class="mb-0">${row.emp_name}</h6>
                                <small class="text-muted">#${row.emp_id} · ${row.emp_position}</small>
                            </div>
                            <span class="badge badge-themeSuccess">Approved</span>
                        </div>
                        <div class="small mt-2">
                            <div><strong>Date:</strong> ${row.date_discussion}</div>
                            <div><strong>Time:</strong> ${row.start_time} - ${row.end_time}</div>
                            <div><strong>Place:</strong> ${row.Meeting_Place}</div>
                        </div>
                    </div>
                </div>`;
            });
            $('#approvedListContainer').html(html);
        }
    });
}
$(document).ready(loadApprovedList);

// Click an approved card → load into stage-2
$(document).on("click", ".approved-checkin", function() {
    let row = $(this).data('row');
    $('#checkin_hidden_id').val(row.id);
    $('#emp_hidden_id').val(row.emp_id);
    $('[name="date_discussion"]').val(row.date_discussion ? row.date_discussion.split('-').reverse().join('/') : '').prop('readonly', true);
    $('[name="start_time"]').val(row.start_time).prop('readonly', true);
    $('[name="end_time"]').val(row.end_time).prop('readonly', true);
    $('[name="Meeting_Place"]').val(row.Meeting_Place).prop('readonly', true);
    $('#Append_id').html(`<div class="img-circle userImg-block me-lg-4 me-md-3 me-2">
                            <img src="${row.emp_photo}" alt="user">
                        </div>
                        <div>
                            <h4 class="mb-md-2 mb-1">${row.emp_name}<span class="badge badge-themeNew">${row.emp_id}</span></h4>
                            <p>${row.emp_position}</p>
                        </div>`);
    $('#stage2Fields').removeClass('d-none');
    $('#sendApprovalBtn').addClass('d-none');
    $('#submitFinalBtn').removeClass('d-none');
    $('#cancelStage2Btn').removeClass('d-none');
    $('html, body').animate({ scrollTop: $('#stage2Fields').offset().top - 100 }, 300);
});

</script>
@endsection
