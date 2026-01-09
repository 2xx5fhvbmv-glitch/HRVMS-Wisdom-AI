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
                            <span>Incident</span>
                            <h1>{{ $page_title }}</h1>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="row g-md-3 g-2 align-items-center">
                        <div class="col-xl-3 col-lg-5 col-sm-6 ">
                            <div class="input-group">
                                <input type="search" class="form-control" id="searchInput" placeholder="Search" />
                                <i class="fa-solid fa-search"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="table-responsive mb-md-3 mb-2">
                    <table id="preventiveTable" class="table w-100 mb-0">
                        <thead>
                            <tr>
                                <th>Incident Name</th>
                                <th>Preventive Measures</th>
                                <th>Date </th>
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
    $(document).ready(function(){
        getPreventives();
        $('#searchInput').on('keyup change', function () {
            getPreventives();
        })
    });
    function getPreventives(){
        if ($.fn.DataTable.isDataTable("#preventiveTable")) {
            $("#preventiveTable").DataTable().destroy();
        }

        $('#preventiveTable').DataTable({
            "searching": false,
            "bLengthChange": false,
            "bFilter": true,
            "bInfo": true,
            "bAutoWidth": false,
            "scrollX": true,
            "iDisplayLength": 10,
            processing: true,
            serverSide: true,
            order: [[2, 'desc']],
            ajax: {
                url: "{{ route('incident.preventive') }}",
                data: function (d) {
                    d.searchTerm = $('#searchInput').val();
                },
                type: "GET",
            },
            columns: [
                { data: 'incident_name', name: 'incident_name', className: 'text-nowrap' },
                { data: 'preventive_measures', name: 'preventive_measures', className: 'text-nowrap' },
                { data: 'updated_at', name: 'updated_at', className: 'text-nowrap' },
                {data:'created_at',visible:false,searchable:false},
            ],
            error: function (xhr) {
                console.log(xhr.responseText);
            }
        });
    }
    
</script>
@endsection