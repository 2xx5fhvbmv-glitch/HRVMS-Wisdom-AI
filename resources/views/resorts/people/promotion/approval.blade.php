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

            <div class="card card-promoApprovalReview">

                <div class="promoApprovalReview-block bg-themeGrayLight mb-4">
                    <div class="row g-lg-4 g-2">
                        <div class="col-sm">
                            <label class="form-label">INITIATED BY</label>
                            <div class="d-flex align-items-center">
                                <div class="img-circle userImg-block  me-md-3 me-2">
                                    <img src="{{Common::getResortUserPicture($promotion->createdBy->id ?? null)}}" alt="user">
                                </div>
                                <div>
                                    <h4 class="fw-600">
                                        {{$promotion->createdBy->first_name}} {{$promotion->createdBy->last_name}} 
                                        <span class="badge badge-white">#{{$promotion->createdBy->GetEmployee->Emp_id}}</span>
                                    </h4>
                                    <p>{{$promotion->createdBy->GetEmployee->position->position_title}}</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-auto">
                            <span class="date"><i class="fa-regular fa-calendar me-2"></i>
                                {{ \Carbon\Carbon::parse($promotion->created_at)->format('d M Y - g:i a') }}
                            </span>
                        </div>
                    </div>
                </div>
                <div class="row g-md-4 g-3 mb-md-4 mb-3">
                    <div class="col-lg-6">
                        <div class="cardBorder-block">
                            <div class="card-title">
                                <h3>Current Employment Details</h3>
                            </div>
                            <div class="d-flex align-items-center mb-3">
                                <div class="img-circle userImg-block me-xl-4 me-md-3 me-2">
                                    <img src="{{Common::getResortUserPicture($promotion->employee->Admin_Parent_id ?? null)}}" alt="user">
                                </div>
                                <div>
                                    <h4 class="fw-600 mb-1">{{$promotion->employee->resortAdmin->full_name}} </h4>
                                    <span class="badge badge-themeNew">#{{$promotion->employee->Emp_id}}</span>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table-lableNew w-100">
                                    <tr>
                                        <th>Position:</th>
                                        <td>{{$promotion->currentPosition->position_title}}</td>
                                    </tr>
                                    <tr>
                                        <th>Department:</th>
                                        <td>{{$promotion->currentPosition->department->name}}</td>
                                    </tr>
                                    @php
                                        use Carbon\Carbon;

                                        $joiningDate = Carbon::parse($promotion->employee->joining_date);
                                        $formattedDate = $joiningDate->format('d F Y');
                                        $diff = $joiningDate->diff(Carbon::now());

                                        $years = $diff->y;
                                        $months = $diff->m;

                                        $duration = '';
                                        if ($years > 0) {
                                            $duration .= $years . ' year' . ($years > 1 ? 's' : '');
                                        }
                                        if ($months > 0) {
                                            $duration .= ($duration ? ' ' : '') . $months . ' month' . ($months > 1 ? 's' : '');
                                        }
                                    @endphp
                                    <tr>
                                        <th>Joining Date:</th>
                                        <td>{{ $formattedDate }} ({{ $duration }})</td>
                                    </tr>
                                    <tr>
                                        <th>Basic Salary:</th>
                                        <td>{{$promotion->current_salary}}</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="cardBorder-block h-100">
                            <div class="card-title">
                                <h3>Proposed Changes</h3>
                            </div>
                            <div class="table-responsive">
                                <table class="table-lableNew table-proposedChanges w-100">
                                    <tr>
                                        <th>New Position:</th>
                                        <td>{{$promotion->currentPosition->position_title}}
                                            <i class="fa-regular fa-arrow-right mx-2"></i>
                                            <b>{{$promotion->newPosition->position_title}}</b>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Salary Increment:</th>
                                        <td>${{$promotion->current_salary}}<i class="fa-regular fa-arrow-right mx-2"></i>
                                        <b>${{$promotion->new_salary}}</b>
                                            <span class="badge badge-themeNew1">{{$promotion->salary_increment_percent}}% increase</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Effective Date:</th>
                                        <td>{{ \Carbon\Carbon::parse($promotion->effective_date)->format('d M Y')}}</td>
                                    </tr>
                                    <tr>
                                        <th>Comments:</th>
                                        <td>{{$promotion->comments}}</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            
                @if($promotion->status != 'Approved' && $promotion->status != 'Rejected')
                    <form id="review-approval" data-parsley-validate>
                        <div class="mb-3"> 
                            <label for="comments" class="form-label">COMMENTS</label>
                            <textarea id="comments" rows="3" class="form-control"
                                placeholder="Add your review comments here..."></textarea>
                        </div>
                        <div class="mb-3 d-none" id="followup-container">
                            <label for="followup_date" class="form-label">Follow-Up Date</label>
                            <input type="text" id="followup_date" name="followup_date" class="form-control datepicker">
                        </div>
                        <div class="card-footer">
                            <div class="row justify-content-end g-2">
                                <div class="col-auto">
                                    <button type="button" class="btn btn-themeSkyblue btn-sm btn-action" data-action="On Hold">On Hold</button>
                                </div>
                                <div class="col-auto">
                                    <button type="button" class="btn btn-themeBlue btn-sm btn-action" data-action="Approved">Approve</button>
                                </div>
                                <div class="col-auto">
                                    <button type="button" class="btn btn-themeDanger btn-sm btn-action" data-action="Rejected">Reject</button>
                                </div>
                            </div>
                        </div>
                    </form>
                @endif
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
        const promotionApprovalUrl = @json(route('promotion.review.action', ['id' => '__ID__', 'action' => '__ACTION__']));

        $('.btn-action').on('click', function () {
            let action = $(this).data('action');
            let comments = $('#comments');
            let followUpDate = $('#followup_date');
            let promotionId = "{{ $promotion->id }}";
            const finalUrl = promotionApprovalUrl.replace('__ID__', promotionId).replace('__ACTION__', action);

            // Dynamically set validation based on action
            if (action === 'Rejected' || action === 'On Hold') {
                comments.attr('required', 'required');
            } else {
                comments.removeAttr('required');
            }

             // Toggle follow-up field
            if (action === 'On Hold') {
                $('#followup-container').removeClass('d-none');
                followUpDate.attr('required', 'required');
            } else {
                $('#followup-container').addClass('d-none');
                followUpDate.removeAttr('required');
            }
            // comments.parsley().reset();

            // Validate using Parsley
            if (!comments.parsley().validate()) {
                return;
            }

            let form1 = $("#review-approval").parsley(); // Initialize Parsley

            if (!form1.isValid()) {
                form1.validate();
                return false;
            }
            $.ajax({
                url: finalUrl,
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    promotion_id: promotionId,
                    action: action,
                    comments: comments.val(),
                    followup_date: followUpDate.val()

                },
                success: function (response) {
                    console.log(response);
                    if (response.status === 'success') {
                        // alert(response.message);
                        toastr.success(response.message, "Success", {
                            positionClass: 'toast-bottom-right'
                        });
                        setTimeout(() => {
                            window.location.href = response.redirect_url;
                        }, 2000);
                    } else {
                        toastr.error('Something went wrong. Please try again.', "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                        // alert('Something went wrong. Please try again.');
                    }
                },
                error: function (xhr) {
                    let res = xhr.responseJSON;
                    let message = res?.message || 'Something went wrong. Please try again.';
                    toastr.error(message, "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                }

            });
        });
    });
</script>
@endsection