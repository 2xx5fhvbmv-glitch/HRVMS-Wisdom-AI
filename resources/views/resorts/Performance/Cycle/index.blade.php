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
                        <span>Performance</span>
                        <h1>{{ $page_title }}</h1>
                    </div>
                </div>
                <div class="col-auto ms-auto">
                    @if($canCreateCycle ?? false)
                        <a href="{{ route('Performance.create') }}" class="btn perf-btn-hero">Create New Cycle</a>
                    @endif
                </div>
            </div>
        </div>


        <div class="card ">
            <div class="card-header mb-md-4">
                <div class="row g-md-3 g-2 align-items-center">
                    <div class="col-xl-3 col-lg-5 col-md-7 col-sm-8 ">
                        <div class="input-group">
                            <input type="search" class="form-control cycleSearch" placeholder="Search by cycle name" />
                            <i class="fa-solid fa-search"></i>
                        </div>
                    </div>
                    <div class="col-xl-2 col-md-3 col-sm-4 col-7">
                        <select class="form-select" id="cycleYearFilter">
                            <option value="">All Years</option>
                            @foreach($availableYears as $year)
                                <option value="{{ $year }}" {{ $selectedYear == $year ? 'selected' : '' }}>{{ $year }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            <div class="PerformanceCyc-main">
                @if($PerformanceCycle->isNotEmpty())
                    @foreach ($PerformanceCycle as $p)
                        <div class="PerformanceCyc-block bg-themeGrayLight">
                            <div class="PerformanceCyc-head">
                                <div class="">
                                    <h5>{{$p->Cycle_Name}} 
                                          
                                        @if($p->status == "Pending")
                                            <span class="badge badge-danger">{{ $p->status }}</span>
                                        @elseif($p->status =="OnGoing")
                                            <span class="badge badge-success">{{ $p->status }}</span>
                                        @else
                                            <span class="badge badge-danger">{{ $p->status }}</span>
                                        @endif
                                    </h5>
                                    <p><img src="{{ URL::asset('resorts_assets/images/users.svg') }}" alt="icon"> {{ $p->child_count }} Employees</p>
                                </div>
                                <div>
                                    <a href="{{ route('Performance.cycle.view', base64_encode($p->id)) }}" class="btn perf-btn-secondary btn-xsmall"><i class="fa-regular fa-eye me-1"></i> View</a>
                                    <a href="#" class="btn-tableIcon btnIcon-danger cycle-delete" data-id="{{ base64_encode($p->id) }}"><i class="fa-regular fa-trash-can"></i></a>
                                </div>
                            </div>
                            <div class="row gx-md-4 g-3">
                                <div class="col-lg-3 col-sm-6">
                                    <div class="d-flex bg-white">
                                        <p>Manager Reviews</p>
                                        <h3>{{ $p->ManagerReview }}</h3>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-sm-6">
                                    <div class="d-flex bg-white">
                                        <p>Self Reviews</p>
                                        <h3>{{ $p->SelfReview }}</h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>

    </div>
</div>
@include('resorts.Performance._performance_buttons_v2_styles')
@endsection

@section('import-css')
<style>
    /* .btnIcon-danger is a shared global class (also used by benifitgrid)
       with an old dark-red (#A90000), not the agreed brand Critical
       scarlet — override just this page's delete icon rather than the
       shared class, which would silently recolor the other module too. */
    .btn-tableIcon.cycle-delete { color: #FF2400; background: rgba(255,36,0,.09); }
    .btn-tableIcon.cycle-delete:hover { color: #fff; background: #FF2400; }
</style>
@endsection

@section('import-scripts')
<script type="module">
   

$(document).ready(function(){

    // Year filter — reload page with selected year
    $('#cycleYearFilter').on('change', function() {
        var year = $(this).val();
        var url = "{{ route('Performance.cycle') }}";
        if (year) {
            url += '?year=' + encodeURIComponent(year);
        }
        window.location.href = url;
    });

    // Client-side search filter on cycle cards
    $('.cycleSearch').on('keyup', function() {
        var term = $(this).val().toLowerCase();
        $('.PerformanceCyc-block').each(function() {
            var title = $(this).find('h5').text().toLowerCase();
            $(this).toggle(title.indexOf(term) !== -1);
        });
    });

    $(document).on('click', '.cycle-delete', function (e) {
        
            e.preventDefault();
            var $button = $(this);
            var $row = $button.closest("tr");

            // Get the division ID from the data attribute
            var main_id = $(this).data('id');

            Swal.fire({
                title: 'Are you sure you want to delete?',
                text: 'This cannot be undone',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes',
                cancelButtonText: 'No',
                confirmButtonColor: "#FF2400"
             }).then((result) => {
                if (result.isConfirmed)
                {

                    $.ajax({
                        type: "delete",
                        url: "{{ route('Performance.cycle.destory','') }}/"+main_id,
                        dataType: "json",
                    }).done(function(result) {
                        if (result.success == true) {
                            toastr.success(result.message, "Success", {
                                positionClass: 'toast-bottom-right'
                            });
                            setTimeout(function() { location.reload(); }, 600);
                        }
                            else {
                            toastr.error(result.message, "Error", {
                                positionClass: 'toast-bottom-right'
                            });
                        }
                    }).fail(function(error) {
                        toastr.error("Something went wrong", "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    });
                }
            });
        });
});
</script>
@endsection
