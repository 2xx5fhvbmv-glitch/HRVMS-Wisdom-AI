@extends('resorts.layouts.app')
@section('page_tab_title' ,$page_title)

@if ($message = Session::get('success'))
<div class="alert alert-success">
	<p>{{ $message }}</p>
</div>
@endif

@section('content')
<style>
    #monthly-checkin-details-hero { padding-bottom: 40px; }
    @media (max-width: 575.98px) {
        #monthly-checkin-details-hero { padding-bottom: 0; }
    }
</style>
<div class="body-wrapper pb-5">
    <div class="container-fluid">
        <div class="page-hedding" id="monthly-checkin-details-hero">
            <div class="row  g-3">
                <div class="col-auto">
                    <div class="page-title">
                        <span>Performance</span>
                        <h1>{{$page_title}}</h1>
                    </div>
                </div>
                <!-- <div class="col-auto ms-auto"><a href="#" class="btn btn-theme">Add New</a></div> -->
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <div class="row g-2 align-items-center">
                    <div class="col-md">
                        <div class="employeesDetails-block d-flex">
                            <div class="img-circle userImg-block "><img src="{{$monthly->profileImg}}" alt="user">
                            </div>
                            <div>
                                <h4>{{$monthly->first_name}} {{$monthly->last_name}} <span class="badge badge-themeNew">{{$monthly->OrignalEmp_id}}</span></h4>
                                <p>{{$monthly->PositionName}}</p>
                                <!-- <div class="row flex-md-nowrap g-2">
                                    <div class="col-auto">
                                        <div class="rating">
                                            <i class="fa-solid fa-star"></i>
                                            <i class="fa-solid fa-star"></i>
                                            <i class="fa-solid fa-star"></i>
                                            <i class="fa-solid fa-star disable"></i>
                                            <i class="fa-solid fa-star disable"></i>
                                        </div>
                                    </div>
                                    <div class="col-auto"><span class="badge badge-themeYellow border-0"><em>Added
                                                In PIP</em></span></div>
                                    <div class="col-auto"><span class="badge badge-themeRed"><em>Appraisal
                                                Pending</em></span></div>
                                </div> -->
                            </div>
                        </div>
                    </div>
                    <!-- <div class="col-auto">
                        <a href="#" class="btn btn-themeBlue">View Appraisal Form</a>
                    </div>
                    <div class="col-auto ">
                        <a href="#" class="btn btn-themeSkyblue">Remove From PIP</a>
                    </div> -->

                </div>
            </div>
            <!-- data-Table  -->
            <div class="table-responsive mb-2">
                <table id="table-employeesDetails" class="table table-employeesDetails w-100">
                    <thead>
                        <tr>
                            <th>Date Of Discussion</th>
                            <th>Time</th>
                            <th>Area Of Discussion</th>
                            <th>Area Of Improvement</th>
                            <th>Comment</th>
                            <th>Time Line</th>
                            <th>Training</th>
                            <th>Duration</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
              
                    </tbody>
                </table>
            </div>

        </div>

        @if($reviewRounds->isNotEmpty())
        <div class="card mt-3">
            <div class="card-header">
                <h5 class="mb-0">Employee Acknowledgement History</h5>
            </div>
            <div class="card-body">
                @foreach($reviewRounds as $round)
                <div class="border rounded p-3 mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <strong>History {{ $round->round }}</strong>
                        @if($round->employee_response === 'Acknowledged')
                            <span class="badge badge-themeGreen">Acknowledged &amp; Approved</span>
                        @elseif($round->employee_response === 'Declined')
                            <span class="badge badge-themeRed">Employee Declined</span>
                        @else
                            <span class="badge badge-themeWarning">Pending Employee Response</span>
                        @endif
                    </div>
                    <div class="mb-2"><strong>Area of Improvement:</strong> {{ $round->area_of_improvement }}</div>
                    @if($round->hod_comment)
                        <div class="mb-2"><strong>HOD Comment:</strong> {{ $round->hod_comment }}</div>
                    @endif
                    @if($round->employee_response !== 'Pending')
                        <div class="mb-2"><strong>Employee Comment:</strong> {{ $round->employee_comment }}</div>
                    @endif
                    @if($round->employee_response === 'Declined' && $round->decline_reason)
                        <div class="mb-2"><strong>Decline Reason:</strong> {{ $round->decline_reason }}</div>
                    @endif
                </div>
                @endforeach

                @if($reviewRounds->last()->employee_response === 'Declined')
                <button type="button" class="btn perf-btn-positive btn-sm" id="reinitiateBtn">Re-initiate Area of Improvement</button>
                @endif
            </div>
        </div>
        @endif

    </div>
</div>

<div class="modal fade" id="reinitiateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form id="reinitiateForm">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Re-initiate Area of Improvement</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label">Area of Improvement <span class="text-danger">*</span></label>
                    <textarea name="area_of_improvement" class="form-control mb-3" rows="3" required></textarea>
                    <label class="form-label">Comment</label>
                    <textarea name="comment" class="form-control" rows="3"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn perf-btn-neutral btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn perf-btn-positive btn-sm">Send to Employee</button>
                </div>
            </div>
        </form>
    </div>
</div>
@include('resorts.Performance._performance_buttons_v2_styles')
@endsection

@section('import-css')
@endsection

@section('import-scripts')
<script>
$(document).ready(function () {

tablemonthlyCheck();
});


function tablemonthlyCheck()
{

    if ($.fn.DataTable.isDataTable("#table-employeesDetails"))
    {
        $("#table-employeesDetails").DataTable().destroy();
    }
    var divisionTable = $("#table-employeesDetails").DataTable({
    searching: false,
    bLengthChange: false,
    bFilter: true,
    bInfo: true,
    bAutoWidth: false,
    scrollX: true,
    iDisplayLength: 6,
    processing: true,
    serverSide: true,
    order: [[9, 'desc']],
    ajax: {
        url: "{{ route('Performance.MonltyCheckInDetailsPageList') }}",
        type: 'GET',
        data: function(d) {
            d.Parent_id =  " {{base64_encode($monthly->Parent_m_id)}}";

        }
    },
        columns: [
            { data: 'DateOfDisussion', name: 'DateOfDisussion'},
            { data: 'Time', name: 'Time'},
            { data: 'AreaOfDiscussion', name: 'AreaOfDiscussion' },
            { data: 'AreaOfImprovement', name: 'AreaOfImprovement' },
            { data: 'Comment', name: 'Comment' },
            { data: 'TimeLine', name: 'TimeLine' },
            { data: 'Training', name: 'Training' },
            { data: 'Duration', name: 'Duration' },
            { data: 'Status', name: 'Status' },
            {data:'created_at', visible:false,searchable:false},
        ]
    });
}

$(document).on('click', '#reinitiateBtn', function() {
    $('#reinitiateForm')[0].reset();
    $('#reinitiateModal').modal('show');
});

$(document).on('submit', '#reinitiateForm', function(e) {
    e.preventDefault();
    $.ajax({
        url: "{{ route('Performance.MonltyCheckIn.reinitiate', $monthly->Parent_m_id) }}",
        type: 'POST',
        data: $(this).serialize(),
        success: function(res) {
            if (res.success) {
                $('#reinitiateModal').modal('hide');
                toastr.success(res.message, 'Success', { positionClass: 'toast-bottom-right' });
                setTimeout(() => location.reload(), 600);
            }
        },
        error: function(xhr) {
            const errs = xhr.responseJSON?.errors;
            if (errs) {
                let msg = '';
                $.each(errs, function(k, v) { msg += v + '<br>'; });
                toastr.error(msg, 'Validation Error', { positionClass: 'toast-bottom-right' });
            } else {
                toastr.error(xhr.responseJSON?.message || 'Failed to re-initiate', 'Error', { positionClass: 'toast-bottom-right' });
            }
        }
    });
});
</script>
@endsection
