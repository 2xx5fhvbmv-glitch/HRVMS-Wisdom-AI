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
                            <span>Learning & Development</span>
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

                        <div class="col-xl-2 col-lg-4 col-md-5  col-6">
                            <input type="text" name="dateFilter" id="dateFilter" class="form-control datepicker"/>
                        </div>
                    </div>
                   
                </div>
              
                <table id="absentees-table" class="table table-striped">
                    <thead>
                        <tr>
                            <th>Employee Name</th>
                            <th>Learning Name</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
@endsection

@section('import-css')
<style></style>
@endsection

@section('import-scripts')
<script>
    $(document).ready(function(){
        getAbsentees();
        $('#searchInput, #dateFilter').on('keyup change', function () {
            getAbsentees();
        });
        $('#dateFilter').datepicker({
            format: 'dd/mm/yyyy',
            autoclose: true
        });
    })
    function getAbsentees() {
        if ($.fn.DataTable.isDataTable('#absentees-table')) {
            $('#absentees-table').DataTable().destroy();
        }

        $('#absentees-table').DataTable({
            searching: false,
            lengthChange: false,
            filter: true,
            info: true,
            autoWidth: false,
            scrollX: true,
            pageLength: 6,
            processing: true,
            serverSide: false,
            ajax: {
                url: "{{ route('learning.absentees.getdata') }}",
                type: 'GET',
                data: function (d) {
                    d.searchTerm = $('#searchInput').val();

                    let selectedDate = $('#dateFilter').val();
                    if (selectedDate) {
                        let parts = selectedDate.split('/');
                        d.date = `${parts[2]}-${parts[1].padStart(2, '0')}-${parts[0].padStart(2, '0')}`;
                    } else {
                        d.date = '';
                    }
                }
            },
            columns: [
                {
                    data: 'profile_picture',
                    name: 'profile_picture',
                    render: function (data, type, row) {
                        return `
                            <div class="tableUser-block">
                                <div class="img-circle">
                                    <img src="${data}" alt="user">
                                </div>
                                <span class="userApplicants-btn">${row.employee_name}</span>
                            </div>
                        `;
                    }
                },
                { data: 'learning_name', name: 'learning_name' },
                {
                    data: 'attendance_date',
                    name: 'attendance_date',
                    render: function (data) {
                        return moment(data).format('DD MMM YYYY');
                    }
                }
            ]
        });

    }
</script>
@endsection