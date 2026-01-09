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

    </div>
</div>
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

</script>
@endsection
