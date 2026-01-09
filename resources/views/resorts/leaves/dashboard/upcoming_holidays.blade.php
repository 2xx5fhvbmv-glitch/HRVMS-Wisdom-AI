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
                            <span>Leave</span>
                            <h1>{{ $page_title }}</h1>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-title">
                    <h3>{{ $page_title }}</h3>
                </div>
                <table class="table"  id="upcoming-holidays" >
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Day</th>
                            <th>Holiday Name</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
 @endsection

@section('import-css')
@endsection

@section('import-scripts')
<script>

$(document).ready(function() {
    $('#upcoming-holidays').DataTable({
        searching: false,
        lengthChange: false,
        info: true,
        autoWidth: false,
        scrollX: true,
        pageLength: 6,
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("resort.upcomingholiday.list") }}',
            type: 'GET',
        },
        columns: [
            { data: 'PublicHolidaydate', name: 'PublicHolidaydate', className: 'text-nowrap' },
            { data: 'day', name: 'day', className: 'text-nowrap' },
            { data: 'PublicHolidayName', name: 'PublicHolidayName', className: 'text-nowrap' },
        ],
        error: function (xhr, error, code) {
            console.error('Error:', error);
            console.log('XHR Response:', xhr.responseText);
        }
    });
});
</script>
@endsection