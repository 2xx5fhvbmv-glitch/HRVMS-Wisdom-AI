@extends('resorts.layouts.app')
@section('page_tab_title' ,$page_title)

@section('content')
    <div class="body-wrapper pb-5">
        <div class="container-fluid">
            <div class="page-hedding page-appHedding">
                <div class="row justify-content-between g-md-2 g-1">
                    <div class="col-auto">
                        <div class="page-title">
                            <span>Talent Acquisition</span>
                            <h1>{{ $page_title }}</h1>
                        </div>
                    </div>
                    <div class="col-auto ms-auto">
                        <a href="javascript:history.back()" class="btn btn-themeLightNew"><i class="fa-solid fa-arrow-left me-1"></i>Back</a>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-header">
                    <div class="row g-md-3 g-2 align-items-center">
                        <div class="col-auto">
                            <h5 class="mb-0">Upcoming Interviews (Next 7 Days)</h5>
                        </div>
                    </div>
                </div>
                <div class="list-main">
                    <div class="table-responsive">
                        <table class="table table-collapseNew table-reminders">
                            <thead>
                                <tr>
                                    <th>Applicant</th>
                                    <th>Position</th>
                                    <th>Department</th>
                                    <th>Interview Date</th>
                                    <th>Resort Time</th>
                                    <th>Applicant Time</th>
                                    <th>Days Until</th>
                                    <th>Meeting</th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('import-scripts')
<script>
    $(document).ready(function() {
        loadRemindersTable();
    });

    function loadRemindersTable() {
        if ($.fn.DataTable.isDataTable('.table-reminders')) {
            $('.table-reminders').DataTable().destroy();
        }
        $('.table-reminders').DataTable({
            searching: false,
            bLengthChange: false,
            bFilter: true,
            bInfo: true,
            bAutoWidth: false,
            scrollX: true,
            iDisplayLength: 10,
            processing: true,
            serverSide: true,
            order: [[3, 'asc']],
            ajax: {
                url: "{{ route('resort.ta.GetReviewReminders') }}",
                type: 'GET'
            },
            columns: [
                { data: 'first_name', name: 'first_name', render: function(data, type, row) {
                    return '<div class="tableUser-block"><div class="img-circle"><img src="'+row.profileImg+'" alt="user"></div><span>'+row.name+'</span></div>';
                }},
                { data: 'position_title', name: 'position_title' },
                { data: 'department', name: 'department' },
                { data: 'interview_date', name: 'interview_date' },
                { data: 'ResortInterviewtime', name: 'ResortInterviewtime' },
                { data: 'ApplicantInterviewtime', name: 'ApplicantInterviewtime' },
                { data: 'days', name: 'days', orderable: false, searchable: false },
                { data: 'meeting', name: 'meeting', orderable: false, searchable: false }
            ],
            drawCallback: function() {
                $('[data-bs-toggle="tooltip"]').tooltip();
            }
        });
    }
</script>
@endsection
