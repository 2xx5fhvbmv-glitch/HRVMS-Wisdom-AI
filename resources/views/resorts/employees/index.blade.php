@extends('resorts.layouts.app')
@section('page_tab_title', $page_title)


@section('content')
    <style>
        #employee-list-hero { padding-bottom: 40px; }
        @media (max-width: 575.98px) {
            #employee-list-hero { padding-bottom: 0; }
        }
    </style>
    <div class="body-wrapper pb-5">
        <div class="container-fluid">
            <div class="page-hedding" id="employee-list-hero">
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
                "searching": true,
                "processing": true,
                "serverSide": true,
                "ordering": true,
                "paging": true,
                "iDisplayLength": 10,
                "ajax": {
                    "url": "{{ route('resort.employeelist') }}",
                    "type": "get",
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
