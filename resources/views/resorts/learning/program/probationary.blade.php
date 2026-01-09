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
                        
                    </div>
                </div>
                <!-- data-Table -->
                <div class="table-responsive mb-md-3 mb-2">
                    <table id="probationary-learning-table" class="table table-LearningProgram w-100 mb-0">
                        <thead>
                            <tr>
                                <th>Probationary Learning</th>
                                <th>Completion Days</th>
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
<script type="text/javascript">
    $(document).ready(function () {
        getProbationaryLearningPrograms();

        $("#searchInput").on("input change", debounce(function () {
            getProbationaryLearningPrograms();
        }, 300));
    });

    function getProbationaryLearningPrograms() {
        $('#probationary-learning-table tbody').empty();
        if ($.fn.DataTable.isDataTable('#probationary-learning-table')) {
            $('#probationary-learning-table').DataTable().destroy();
        }

        var programTable = $('#probationary-learning-table').DataTable({
            searching: false,
            bLengthChange: false,
            bFilter: true,
            bInfo: true,
            bAutoWidth: false,
            scrollX: true,
            iDisplayLength: 6,
            processing: true,
            serverSide: true,
            ajax: {
                url: '{{ route("learning.probationary-programs.list") }}',
                type: 'GET',
                data: function (d) {
                    d.searchTerm = $('#searchInput').val();  // Send search input
                }
            },
            columns: [
                { data: 'program', name: 'Probationary Learning', className: 'text-nowrap' },
                { data: 'completion_days', name: 'Completion Days', className: 'text-nowrap' }, // Fixed Notify Before Days
                
            ]
        });
    }
    function debounce(func, delay) {
        let timer;
        return function () {
            clearTimeout(timer);
            timer = setTimeout(() => func.apply(this, arguments), delay);
        };
    }
</script>
@endsection