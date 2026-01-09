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
                    <div class="col-auto ms-auto"><a href="{{route('Performance.CreateMonltyCheckIn')}}" class="btn btn-theme @if(App\Helpers\Common::checkRouteWisePermission('Performance.CreateMonltyCheckIn',config('settings.resort_permissions.create'))== false) d-none @endif">Add New</a></div>
                </div>
            </div>

        <div class="card">
            <div class="card-header">
                <div class="row g-md-3 g-2 align-items-center">
                    <div class="col-xl-3 col-lg-5 col-md-7 col-sm-8 ">
                        <div class="input-group">
                            <input type="search" class="form-control search" placeholder="Search" />
                            <i class="fa-solid fa-search"></i>
                        </div>
                    </div>
                    <div class="col-xl-2 col-md-3 col-sm-4 col-7">
                                            <input type="text" id="date_discussion"
                                            required
                                            data-parsley-required-message="Date of discussion is required"
                                            name="date_discussion" class="form-control" placeholder="Select Date">
                    </div>

                </div>
            </div>
            <table id="table-monthlyCheck" class="table data-Table table-monthlyCheck w-100">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name </th>
                        <th>Position</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Summary</th>
                        <th>Training</th>
                        <th>Duration</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>



        </div>
    </div>
</div>
@endsection

@section('import-css')
@endsection

@section('import-scripts')
<script>
$(document).ready(function () {

    $("#date_discussion").datepicker({
        format: 'dd/mm/yyyy',
        autoclose: true,
        todayHighlight: true
    });
    tablemonthlyCheck();
});

$(".search").on("keyup", function () {
    tablemonthlyCheck();

});
$("#date_discussion").on("change", function () {
    tablemonthlyCheck();

});
function tablemonthlyCheck()
{

    if ($.fn.DataTable.isDataTable("#table-monthlyCheck"))
    {
        $("#table-monthlyCheck").DataTable().destroy();
    }
    var divisionTable = $("#table-monthlyCheck").DataTable({
        searching: false,
        bLengthChange: false,
        bFilter: true,
        bInfo: true,
        bAutoWidth: false,
        scrollX: true,
        iDisplayLength: 6,
        processing: true,
        serverSide: true,
        order: [[10, 'desc']], 
        ajax: {
            url: "{{ route('Performance.MonltyCheckIn') }}",
            type: 'GET',
            data: function(d) {
                d.date_discussion = $("#date_discussion").val();
                d.searchTerm = $('.search').val();
            }
        },
        columns: [
            { data: 'ID', name: 'ID'},
            { data: 'Name', name: 'Name'},
            { data: 'Position', name: 'budget' },
            { data: 'Date', name: 'Actual' },
            { data: 'Time', name: 'Time' },
            { data: 'Summary', name: 'Result' },
            { data: 'Training', name: 'Value' },
            { data: 'Duration', name: 'Score' },
            { data: 'Status', name: 'Score' },
            { data: 'Action', name: 'Action' },
            { data:'created_at',visible:false,searchable:false},

            
        ]

    });
}
</script>
@endsection
