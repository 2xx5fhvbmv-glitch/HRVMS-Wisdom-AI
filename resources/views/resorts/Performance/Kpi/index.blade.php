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
                        <h1>{{ $page_title }}</h1>
                    </div>
                </div>
                <div class="col-auto ms-auto">
                    <a href="{{route('Performance.kpi.create')}}" class="btn btn-theme">Create New KPI </a>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <div class="row g-md-3 g-2 align-items-center">
                    <div class="col-xl-3 col-lg-5 col-sm-6 ">
                        <div class="input-group">
                            <input type="search" class="form-control search" placeholder="Search" />
                            <i class="fa-solid fa-search"></i>
                        </div>
                    </div>
                    <div class="col-xl-2 col-lg-4 col-md-5  col-sm-6">
                        @php
                        $currentYear = date('Y');
                        $futureYear = $currentYear + 1;
                    @endphp

                        <select class="form-select Year">
                            <option value="All">Select Duration</option>
                            <option value="{{ $currentYear }}">{{ $currentYear }}</option>
                            <option value="{{ $futureYear }}">{{ $futureYear }}</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="table-responsive">
                <table id="" class="table table-performance-kpilist w-100">
                    <thead>
                        <th>Property Goals</th>
                        <th>Budget/Goal</th>
                        <th>Actual</th>
                        <th>Result</th>
                        <th>Value</th>
                        <th>Score</th>
                        <th>Score %</th>
                        <th>Individual Goals</th>
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
    datatablelist();

    $(".Year").select2({
        placeholder:"select Year"
    });
});

$(document).on("change",".Year",function() {

    datatablelist();
});
$(document).on("keyup",".search",function() {
    datatablelist();
});


function datatablelist()
{
    if ($.fn.DataTable.isDataTable('.table-performance-kpilist'))
    {
        $('.table-performance-kpilist').DataTable().destroy();
    }
    var divisionTable = $('.table-performance-kpilist').DataTable({
        searching: false,
        bLengthChange: false,
        bFilter: true,
        bInfo: true,
        bAutoWidth: false,
        scrollX: true,
        iDisplayLength: 6,
        processing: true,
        serverSide: true,
        order: [[8, 'desc']],
        ajax: {
            url: "{{ route('Performance.kpi.KpiList') }}",
            type: 'GET',
            data: function(d) {
                d.Year = $(".Year").val();
                d.searchTerm = $('.search').val();
            }
        },
        columns: [
            { data: 'PropertyGoals', name: 'PropertyGoals',},
            { data: 'budget', name: 'budget' },
            { data: 'Actual', name: 'Actual' },
            { data: 'Result', name: 'Result' },
            { data: 'Value', name: 'Value' },
            { data: 'Score', name: 'Score' },
            { data: 'ScoreInPercentage', name: 'Score' },
            { data: 'IndividualGoals', name: 'IndividualGoals' },
            {data:'created_at', visible:false,searchable:false},
        ]
    });
}
</script>
@endsection
