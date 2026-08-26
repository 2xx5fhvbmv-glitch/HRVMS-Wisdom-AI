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
                        <!-- <div class="col-xl-2 col-lg-4 col-md-5  col-6">
                            <select id="typeFilter" class="form-select select2t-none">
                                <option value=""> By Learning Type</option>
                                <option value="face-to-face">Face-to-Face</option>
                                <option value="hybrid">Hybrid</option>
                                <option value="online">Online</option>        
                            </select>
                        </div> -->
                        <div class="col-xl-2 col-lg-4 col-md-5  col-6">
                            <input type="text" name="dateFilter" id="dateFilter" class="form-control datepicker"/>
                        </div>
                    </div>
                </div>
                <!-- data-Table -->
                <div class="table-responsive">
                    <table id="table-training" class="table  table-training w-100">
                        <thead>
                        <tr>
                            <th>Training Title</th>
                            <th>Dates</th>
                            <th>Time</th>
                            <th>Venue</th>
                            <th>Status</th>
                            <th>Participants</th>
                            <th>Attendance %</th>
                            <th>Action</th>
                        </tr>
                        </thead>
                        <tbody>

                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div id="trainingDetailModal" class="modal fade" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Training Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="trainingDetailBody">
                    <p class="text-center text-muted my-4">Loading…</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn lnd-btn-neutral" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@include('resorts.Learning._learning_buttons_v2_styles')
@endsection

@section('import-css')
<style></style>
@endsection

@section('import-scripts')
<script>
   $(document).ready(function () {
    const table = $('#table-training').DataTable({
        searching: false,
        lengthChange: false,
        filter: true,
        info: true,
        autoWidth: false,
        scrollX: true,
        pageLength: 6,
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('training.history') }}",
            data: function (d) {
                d.date = $('#dateFilter').val();
            }
        },
        columns: [
            { data: 'title', name: 'title' },
            { data: 'dates', name: 'dates' },
            { data: 'time', name: 'time', orderable: false },
            { data: 'venue', name: 'venue', orderable: false },
            { data: 'status', name: 'status' },
            { data: 'participants', name: 'participants' },
            { data: 'attendance', name: 'attendance' },
            { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-nowrap' }
        ]
    });

    window.viewTrainingDetail = function (id) {
        var $body = $('#trainingDetailBody');
        $body.html('<p class="text-center text-muted my-4">Loading…</p>');
        $('#trainingDetailModal').modal('show');

        $.ajax({
            url: '{{ url("/resort/learning/training-history") }}/' + id + '/detail',
            type: 'GET',
            success: function (response) {
                if (!response.success) {
                    $body.html('<p class="text-danger text-center my-3">' + (response.message || 'Failed to load detail.') + '</p>');
                    return;
                }
                var d = response.data;
                var participantsHtml = '';
                if (d.participants && d.participants.length) {
                    d.participants.forEach(function (p) {
                        participantsHtml +=
                            '<tr>' +
                                '<td>' + $('<div>').text(p.name).html() + '</td>' +
                                '<td>' + p.attended + ' / ' + p.total_marked + '</td>' +
                                '<td>' + p.percentage + '%</td>' +
                            '</tr>';
                    });
                } else {
                    participantsHtml = '<tr><td colspan="3" class="text-center text-muted">No participants.</td></tr>';
                }

                $body.html(
                    '<div class="row g-3">' +
                        '<div class="col-12"><h5 class="mb-1">' + $('<div>').text(d.title || '—').html() + '</h5>' +
                            '<small class="text-muted">' + $('<div>').text(d.category || '').html() + '</small></div>' +
                        '<div class="col-md-6"><strong>Dates:</strong> ' + d.start_date + ' — ' + d.end_date + '</div>' +
                        '<div class="col-md-6"><strong>Time:</strong> ' + d.start_time + ' — ' + d.end_time + '</div>' +
                        '<div class="col-md-6"><strong>Venue:</strong> ' + $('<div>').text(d.venue || '—').html() + '</div>' +
                        '<div class="col-md-6"><strong>Status:</strong> ' + $('<div>').text(d.status || '—').html() + '</div>' +
                        '<div class="col-md-6"><strong>Trainer:</strong> ' + $('<div>').text(d.trainer || '—').html() + '</div>' +
                        '<div class="col-md-6"><strong>Participants:</strong> ' + d.participants_count + '</div>' +
                        '<div class="col-md-6"><strong>Overall Attendance:</strong> ' + d.attendance_percent + '%</div>' +
                        '<div class="col-12"><strong>Description:</strong><p class="mb-0">' + $('<div>').text(d.description || '—').html() + '</p></div>' +
                        '<div class="col-12"><strong>Participants & Attendance</strong>' +
                            '<table class="table table-sm mt-2"><thead><tr><th>Name</th><th>Present / Marked</th><th>%</th></tr></thead>' +
                            '<tbody>' + participantsHtml + '</tbody></table>' +
                        '</div>' +
                    '</div>'
                );
            },
            error: function () {
                $body.html('<p class="text-danger text-center my-3">Failed to load training detail.</p>');
            }
        });
    };

    // Trigger search & filters
    $('#searchInput').on('keyup', function () {
        table.search(this.value).draw();
    });

    $('#dateFilter').on('change', function () {
        table.ajax.reload();
    });

    // Optional: use a datepicker
    flatpickr('#dateFilter', {
        dateFormat: 'Y-m-d',
        allowInput: true,
        appendTo: document.body
    });
});

</script>
@endsection