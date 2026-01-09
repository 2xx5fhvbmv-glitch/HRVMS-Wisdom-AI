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
                            <span>WORKFORCE PLANNING</span>
                            <h1>{{ $page_title }}</h1>
                        </div>
                    </div>
                    @if($available_rank == "HR")
                        <div class="col-auto">
                            <div class="d-flex justify-content-end">
                                <a href="#" class="btn btn-theme">Sent To Finance</a>
                                <a href="#revise-budgetmodal" data-bs-toggle="modal" class="btn btn-white ms-3">Revise
                                    Budget</a>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <div>
                <div class="card">
                    <div class="card-title">
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <div class="d-flex justify-content-start align-items-center">
                                    <a href="#" class="me-3">
                                        <img src="{{ URL::asset('resorts_assets/images/arrow-left.svg')}}" alt="" class="img-fluid" />
                                    </a>
                                    <h3>Management</h3>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="d-flex justify-content-sm-end align-items-center">

                                    <span class="badge badge-dark me-3">
                                        Budget: $12,241
                                    </span>
                                    <a href="#" class="text-lightblue  fw-500 fs-13">WSB : $11,985</a>

                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table id="compare-budgettable" class="table table-compareBudget  w-100">
                            <thead>
                                <tr>
                                    <th colspan="3" class="text-nowrap text-center bg-theme text-white">HOD Budget</th>
                                    <th colspan="3" class="text-nowrap text-center bg-yellow">Wisdom Suggested Budget
                                    </th>
                                </tr>
                                <tr>
                                    <th class="text-nowrap">Positions</th>
                                    <th class="text-nowrap text-center">Headcount</th>
                                    <th class="text-nowrap text-center">Total Budget</th>
                                    <th class="text-nowrap text-center">Headcount</th>
                                    <th class="text-nowrap text-center">Total Budget</th>
                                    <th class="text-nowrap">Justified Reason</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Consultant (Owners Representative)</td>
                                    <td class="text-nowrap text-center"><a href="#"
                                            class="text-theme text-underline">01</a></td>
                                    <td class="text-nowrap text-center">$1,526</td>
                                    <td class="text-nowrap text-center">01</td>
                                    <td class="text-nowrap text-center">$1,526</td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td>Company Secretary</td>
                                    <td class="text-nowrap text-center"><a href="#"
                                            class="text-theme text-underline">02</a></td>
                                    <td class="text-nowrap text-center">$2,100</td>
                                    <td class="text-nowrap text-center">02</td>
                                    <td class="text-nowrap text-center">$2,100</td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td>Office Assistant</td>
                                    <td class="text-nowrap text-center"><a href="#"
                                            class="text-theme text-underline">01</a></td>
                                    <td class="text-nowrap text-center">$1,100</td>
                                    <td class="text-nowrap text-center">01</td>
                                    <td class="text-nowrap text-center">$1,100</td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td>Purchasing executive</td>
                                    <td class="text-nowrap text-center"><a href="#"
                                            class="text-theme text-underline">01</a></td>
                                    <td class="text-nowrap text-center">$1,100</td>
                                    <td class="text-nowrap text-center text-success">02</td>
                                    <td class="text-nowrap text-center text-success">$1,500</td>
                                    <td>
                                        <div class="d-flex align-items-end">
                                            <p class="m-0">HOD requested 03 Safety & Security Managers, but Wisdom AI
                                                suggests 01 based on the past 3 years’ historical data showing
                                                consistent
                                                staffing of 2 managers. </p>
                                            <a href="javascript:void(0)" class="compareBudget-more">More</a>
                                            <a href="javascript:void(0)" class="compareBudget-Less d-none">Less</a>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Logistics Executive</td>
                                    <td class="text-nowrap text-center"><a href="#"
                                            class="text-theme text-underline">01</a></td>
                                    <td class="text-nowrap text-center">$1,100</td>
                                    <td class="text-nowrap text-center ">01</td>
                                    <td class="text-nowrap text-center ">$1,100</td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td>Customs Coordinator</td>
                                    <td class="text-nowrap text-center"><a href="#"
                                            class="text-theme text-underline">03</a></td>
                                    <td class="text-nowrap text-center">$2,500</td>
                                    <td class="text-nowrap text-center text-danger">02</td>
                                    <td class="text-nowrap text-center text-danger">$2,000</td>
                                    <td>
                                        <div class="d-flex align-items-end">
                                            <p class="m-0">HOD requested 03 Safety & Security Managers, but Wisdom AI
                                                suggests 01 based on the past 3 years’ historical data showing
                                                consistent
                                                staffing of 2 managers. </p>
                                            <a href="javascript:void(0)" class="compareBudget-more">More</a>
                                            <a href="javascript:void(0)" class="compareBudget-Less d-none">Less</a>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Male' Office Administrator</td>
                                    <td class="text-nowrap text-center"><a href="#"
                                            class="text-theme text-underline">01</a></td>
                                    <td class="text-nowrap text-center">$1,100</td>
                                    <td class="text-nowrap  text-center">01</td>
                                    <td class="text-nowrap  text-center">$1,100</td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td>Assistant Accountant/Accountant</td>
                                    <td class="text-nowrap text-center"><a href="#"
                                            class="text-theme text-underline">01</a></td>
                                    <td class="text-nowrap text-center">$3,100</td>
                                    <td class="text-nowrap text-center ">01</td>
                                    <td class="text-nowrap text-center ">$3,100</td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td>General Manager</td>
                                    <td class="text-nowrap text-center"><a href="#"
                                            class="text-theme text-underline">02</a></td>
                                    <td class="text-nowrap text-center">$2,100</td>
                                    <td class="text-nowrap text-center text-danger">01</td>
                                    <td class="text-nowrap text-center text-danger">$1,400</td>
                                    <td>
                                        <div class="d-flex align-items-end">
                                            <p class="m-0">Due to forecasted low occupancy, we may not need a Safety &
                                                Security Manager in
                                                June, July, and August</p>
                                            <a href="javascript:void(0)" class="compareBudget-more">More</a>
                                            <a href="javascript:void(0)" class="compareBudget-Less d-none">Less</a>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Island Manager</td>
                                    <td class="text-nowrap text-center"><a href="#"
                                            class="text-theme text-underline">01</a></td>
                                    <td class="text-nowrap text-center">$1,100</td>
                                    <td class="text-nowrap  text-center">01</td>
                                    <td class="text-nowrap text-center ">$1,100</td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td>Safety and Security Manager</td>
                                    <td class="text-nowrap text-center"><a href="#"
                                            class="text-theme text-underline">02</a></td>
                                    <td class="text-nowrap text-center">$1,500</td>
                                    <td class="text-nowrap text-center text-danger">01</td>
                                    <td class="text-nowrap text-center text-danger">$900</td>
                                    <td>
                                        <div class="d-flex align-items-end">
                                            <p class="m-0">Due to forecasted low occupancy, we may not need a Safety &
                                                Security Manager in
                                                June, July, and August</p>
                                            <a href="javascript:void(0)" class="compareBudget-more">More</a>
                                            <a href="javascript:void(0)" class="compareBudget-Less d-none">Less</a>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Island Manager</td>
                                    <td class="text-nowrap text-center"><a href="#"
                                            class="text-theme text-underline">01</a></td>
                                    <td class="text-nowrap text-center">$1,100</td>
                                    <td class="text-nowrap text-center ">01</td>
                                    <td class="text-nowrap text-center ">$1,100</td>
                                    <td></td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th>Total:</th>
                                    <th class="text-center">17</th>
                                    <th class="text-center">$12,241</th>
                                    <th class="text-lightblue text-center">15</th>
                                    <th class="text-lightblue text-center">$11,985</th>
                                    <th></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
 <!-- modal -->
 <div class="modal fade" id="revise-budgetmodal" tabindex="-1" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-small">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="staticBackdropLabel">Revise Budget</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">

                <div class="form-group mb-20">

                    <textarea class="form-control" rows="7" placeholder="Add Comment Regarding Revision"></textarea>
                </div>
            </div>
            <div class="modal-footer justify-content-end">
                <button type="button" class="btn btn-sm btn-themeGray me-2" data-bs-dismiss="modal">Cancel</button>
                <a href="#" class="btn btn-sm btn-theme">Submit</a>
            </div>

        </div>
    </div>
</div>


@endsection

@section('import-css')
@endsection

@section('import-scripts')

<script>
   $(".compareBudget-more").click(function () {
        $(this).addClass("d-none");
        $(this).siblings(".compareBudget-Less").removeClass("d-none");
        $(this).siblings("p").addClass("d-block");
    });
    $(".compareBudget-Less").click(function () {
        $(this).addClass("d-none");
        $(this).siblings(".compareBudget-more").removeClass("d-none");
        $(this).siblings("p").removeClass("d-block");
    });
</script>
@endsection
