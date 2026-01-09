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
                            <th>Participants</th>
                            <th>Attendance %</th>
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
            { data: 'participants', name: 'participants' },
            { data: 'attendance', name: 'attendance' }
        ]
    });

    // Trigger search & filters
    $('#searchInput').on('keyup', function () {
        table.search(this.value).draw();
    });

    $('#dateFilter').on('change', function () {
        table.ajax.reload();
    });

    // Optional: use a datepicker
    $('#dateFilter').datepicker({
        format: 'yyyy-mm-dd',
        autoclose: true
    });
});

</script>
@endsection