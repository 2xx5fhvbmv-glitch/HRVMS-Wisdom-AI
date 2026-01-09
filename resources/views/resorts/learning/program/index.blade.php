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
                            <select id="categoryFilter" class="form-select select2t-none">
                                <option value=""> All Category</option>
                                @if($categories)
                                    @foreach($categories as $category)
                                        <option value="{{$category->id}}">{{$category->category}}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                    </div>
                </div>
                <!-- data-Table -->
                <div class="table-responsive mb-md-3 mb-2">
                    <table id="table-LearningProgram" class="table table-LearningProgram w-100 mb-0">
                        <thead>
                            <tr>
                                <th>Learning Name</th>
                                <th>Description</th>
                                <th>Objectives & Goals</th>
                                <th>Category</th>
                                <th>Target Audience</th>
                                <th>Duration</th>
                                <th>Frequency</th>
                                <th>Delivery Mode</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Lorem Ipsum dummy text</td>
                                <td>Lorem Ipsum is simply dummy text</td>
                                <td>Lorem Ipsum text</td>
                                <td>Lorem Ipsum text</td>
                                <td>1 oct 2019</td>
                                <td>1Day 15 hrs</td>
                                <td>Monthly</td>
                                <td>Offline</td>
                            </tr>
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
            getLearningPrograms();

            $('#searchInput, #categoryFilter').on('keyup change', function () {
                getLearningPrograms();
            });
        });

        function getLearningPrograms(){
            $('#table-LearningProgram tbody').empty();
            if ($.fn.DataTable.isDataTable('#table-LearningProgram ')) {
                $('#table-LearningProgram ').DataTable().destroy();
            }
            var programTable = $('#table-LearningProgram').DataTable({
                searching: false,
                ordering: true,
                bLengthChange: false,
                bFilter: true,
                bInfo: true,
                bAutoWidth: false,
                scrollX: true,
                iDisplayLength: 6,
                processing: true,
                serverSide: true,
                order: [[8, 'desc']],
                ajax: {
                    url: '{{ route("learning.programs.list") }}',
                    data: function (d) {
                        d.searchTerm = $('#searchInput').val();
                        d.category = $('#categoryFilter').val();
                    },
                    type: 'GET',
                },
                columns: [
                    { data: 'name', name: 'Learning Name', className: 'text-nowrap' },
                    { data: 'description', name: 'Description', className: 'text-nowrap' },
                    { data: 'objectives', name: 'Objectives', className: 'text-nowrap' },
                    { data: 'category', name: 'Category', className: 'text-nowrap' },
                    { data: 'target_audience', name: 'Objectives', className: 'text-nowrap' },
                    { data: 'duration', name: 'Duration', className: 'text-nowrap' },
                    { data: 'frequency', name: 'Frequency', className: 'text-nowrap' },
                    { data: 'delivery_mode', name: 'Delivery Mode', className: 'text-nowrap' },
                    {data:'created_at',visible:false,searchable:false},
                ]
            });
        }
        $(document).ready(function () {
            const $userReviewTasksBtn = $(".userReviewTasks-btn");
            const $userReviewTasksWrapper = $(".userReviewTasks-wrapper");

            // Toggle 'end-0' class when userReviewTasks button is clicked
            $userReviewTasksBtn.on("click", function (e) {
                e.stopPropagation(); // Prevent event from bubbling up to document click
                $userReviewTasksWrapper.toggleClass("end-0");
            });

            // Remove 'end-0' class when clicking outside userReviewTasks-btn and userReviewTasks-wrapper
            $(document).on("click", function (e) {
                if (
                    !$userReviewTasksWrapper.is(e.target) &&
                    !$userReviewTasksBtn.is(e.target) &&
                    $userReviewTasksWrapper.has(e.target).length === 0 &&
                    $userReviewTasksBtn.has(e.target).length === 0
                ) {
                    $userReviewTasksWrapper.removeClass("end-0");
                }
            });
        });
    </script>
@endsection