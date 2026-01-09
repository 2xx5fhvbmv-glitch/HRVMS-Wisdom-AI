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
                    <!-- <div class="col-auto  ms-auto">
                        <a class="btn btn-theme" href="#">Initiate Promotion</a>
                    </div> -->
                </div>
            </div>

            <div class="card card-updateEmployeeDetails">
                <div class="updateEmployeeDetails-block bg-themeGrayLight mb-4">
                    <div class="row g-lg-4 g-2">
                        <div class="col">
                            <div class="d-flex align-items-center">
                                <div class="img-circle userImg-block  me-md-3 me-2">
                                    <img src="{{Common::getResortUserPicture($promotion->employee->Admin_Parent_id ?? null)}}" alt="user">                                      
                                </div>
                                <div>
                                    <h4 class="fw-600">
                                        {{$promotion->employee->resortAdmin->full_name}} 
                                        <span class="badge badge-white">
                                            #{{$promotion->employee->Emp_id}}
                                        </span>
                                    </h4>
                                    <p>{{$promotion->currentPosition->position_title}}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="cardBorder-block mb-md-5 mb-4">
                    <div class="row g-md-4 g-3">
                        <div class="col-lg-6">
                            <div class="table-responsive">
                                <table class="table-lableNew w-100">
                                    <tr>
                                        <th>Current Position:</th>
                                        <td>{{$promotion->currentPosition->position_title}}</td>
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
                                        <th>New Salary:</th>
                                        <td><b>${{$promotion->new_salary}}</b></td>
                                    </tr>
                                    <tr>
                                        <th>Updated Job Description:</th>
                                        <td>
                                            <a href="{{ route('job.description.by.position', ['posId' => $promotion->newPosition->id]) }}" class="a-link">
                                                View Job Description
                                            </a>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="table-responsive">
                                <table class="table-lableNew w-100">
                                    <tr>
                                        <th>New Position:</th>
                                        <td><b>{{$promotion->newPosition->position_title}}</b></td>
                                    </tr>
                                    <tr>
                                        <th>Current Salary:</th>
                                        <td>${{$promotion->current_salary}}</td>
                                    </tr>
                                    <tr>
                                        <th>Effective Date:</th>
                                        <td>{{ \Carbon\Carbon::parse($promotion->effective_date)->format('d M Y')}}</td>
                                    </tr>
                                    <tr>
                                        <th>Updated Benefit Grid:</th>
                                        <td>
                                            <a href="{{ route('benefit.grid.view', ['level' => $promotion->new_level]) }}" class="a-link">
                                                View Benefit Grid
                                            </a>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                @if($promotion->status == 'Approved')
                    <div class="card-footer">
                        <div class="row g-2">
                            <div class="col-auto"> 
                                @if($promotion->letter_dispatched == 'No')
                                    <a href="#" class="btn btn-themeNeon btn-sm send-letter" data-id="{{$promotion->id}}" data-type="promotion">
                                        <i class="fa-regular fa-envelope"></i> 
                                        Send Promotion Letter
                                    </a>
                                @endif
                            </div>
                            <div class="col-auto ms-auto"> 
                                @if($promotion->effective_date != $promotion->employee->incremented_date)
                                    <a href="#" class="btn btn-themeSkyblue btn-sm" id="confirmPromotion" data-id="{{$promotion->id}}">Confirm</a>
                                @endif
                            </div>
                        </div>
                    </div>
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
        $('.send-letter').on('click', function () {
            const promotionId = $(this).data('id');
            const type = $(this).data('type');
            $.ajax({
                url: '{{route("promotion.send-letter")}}',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    promotionId: promotionId,
                    type: type
                },
                success: function (response) {
                    // alert(response.message);
                    toastr.success(response.message, "Success", {
                        positionClass: 'toast-bottom-right'
                    });
                }
            });
        });

        $('#confirmPromotion').on('click', function () {
            const promotionId = $(this).data('id');
            $.ajax({
                url: '{{route("promotion.confirm")}}',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    promotionId: promotionId
                },
                success: function (response) {
                    // alert(response.message);
                    toastr.success(response.message, "Success", {
                        positionClass: 'toast-bottom-right'
                    });
                }
            });
        });
    });
</script>
@endsection