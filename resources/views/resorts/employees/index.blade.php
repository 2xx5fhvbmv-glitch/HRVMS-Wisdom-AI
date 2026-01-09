@extends('resorts.layouts.app')
@section('page_tab_title', $page_title)


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
                </div>
            </div>


            <div class="card">

                <table class="table data-Table table-totalEmp" id="employeeTable" style="width:100%">
                    <thead>
                        <tr>
                            <th>Employee Name</th>
                            <th>Department</th>
                            <th>Position</th>
                            <th>Rank</th>
                            <th>Nation</th>
                        </tr>
                    </thead>


                </table>

            </div>
        </div>
    </div>


@endSection
@section('import-css')
@endsection

@section('import-scripts')

    <script>
        $(document).ready(function() {


            if ($.fn.DataTable.isDataTable('#employeeTable')) {
                $('#employeeTable').DataTable().clear().destroy();
            }


            var table = $('#employeeTable').DataTable({
                "searching": false,
                "processing": true, // Show processing indicator

                "ordering": true,
                "paging": false,
                "ajax": {
                    "url": "{{ route('resort.employeelist') }}",
                    "type": "get",
                    "dataSrc": "data", // "dataType": "JSON",
                },
                "columns": [{
                        "data": "name"
                    },
                    {
                        "data": "Department"
                    },
                    {
                        "data": "Position"
                    },
                    {
                        "data": 'Rank'
                    },
                    {
                        "data": 'Nation'
                    }

                ],



            });

        });
    </script>

@endsection
