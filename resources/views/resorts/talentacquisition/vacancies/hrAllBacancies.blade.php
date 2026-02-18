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
                            <span>Talent Acquisition</span>
                            <h1>{{ $page_title }}</h1>
                        </div>
                    </div>
                    <!-- <div class="col-auto">
                        <div class="d-flex justify-content-end">
                            <a href="#" class="btn btn-theme">Request Manning</a>
                        </div>
                    </div> -->
                </div>
            </div>


            <div class="card">
                <div class="card-title">
                    <h3>All Vacancies</h3>
                </div>
                <table class="table"  id="Vacancies" >
                    <thead>
                        <tr>
                            <th>Department</th>
                            <th>Position Title</th>
                            <th>Employee Type</th>
                            <th>Required Starting</th>
                            <th>Budget</th>
                            <th>ReportingTo</th>
                            <th>Rank</th>
                            <th>Status</th>

                        </tr>
                    </thead>


                </table>

            </div>


        </div>
    </div>
    <div class="modal fade" id="respond-rejectModal" tabindex="-1" aria-labelledby="exampleModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-small">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="staticBackdropLabel">Respond</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <textarea class="form-control" rows="7" placeholder="Reason for Rejection"></textarea>
            </div>
            <div class="modal-footer justify-content-center">
                <a href="#" data-bs-dismiss="modal" class="btn btn-themeGray ms-auto">Cancel</a>
                <a href="#" class="btn btn-themeBlue">Submit</a>
            </div>

        </div>
    </div>
</div>
    @endsection

@section('import-css')
@endsection

@section('import-scripts')
<script>

$(document).ready(function() {

    $('#Vacancies tbody').empty();
        var divisionTable = $('#Vacancies').DataTable({
        searching: false,
        bLengthChange: false,
        bFilter: true,
        bInfo: true,
        bAutoWidth: false,
        scrollX: true,
        iDisplayLength: 6,
        processing: true,
        serverSide: true,
        order:[[7, 'desc']],
        ajax: {
            url: '{{ route("resort.ta.GetAllVacancies") }}',
            type: 'GET',
        },
        columns: [
            { data: 'Department', name: 'Department', className: 'text-nowrap' },
            { data: 'Position', name: 'Position', className: 'text-nowrap' },
            {data:'EmployeeType',name:'EmployeeType',className:'text-nowrap'},
        { data: 'Required', name: 'Question', className: 'text-nowrap' },
            { data: 'Budget', name: 'action', className: 'text-nowrap'},
            { data: 'ReportingTo', name: 'ReportingTo', className: 'text-nowrap'},
            { data: 'rank_name', name: 'rank_name', className: 'text-nowrap'},
            { data: 'approval_status', name: 'approval_status', className: 'text-nowrap'},
            {data:'created_at', visible:false,searchable:false},
        ]
    });
});
</script>
@endsection

