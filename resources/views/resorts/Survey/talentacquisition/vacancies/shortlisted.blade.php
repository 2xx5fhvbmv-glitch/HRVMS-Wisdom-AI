@extends('resorts.layouts.app')
@section('page_tab_title' ,"Dashboard")

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
                    {{-- <div class="col-auto ms-auto">
                        <a href="{{ route('resort.ta.shortlistedapplicants') }}" class="btn btn-themeLightNew">Shortlisted Applicants To share Link</a>
                    </div> --}}
                    <!-- <div class="col-auto">
                        <div class="d-flex justify-content-end">
                            <a href="#" class="btn btn-theme">Request Manning</a>
                        </div>
                    </div> -->
                </div>
            </div>


            <div class="card">

                <div class="card-header">
                    <div class="row g-md-3 g-2 align-items-center">
                        <div class="col-xl-3 col-lg-5 col-md-7 col-sm-8 ">
                            <div class="input-group">
                                <input type="search" class="form-control Search" placeholder="Search">
                                <i class="fa-solid fa-search"></i>
                            </div>
                        </div>
                        <div class="col-xl-2 col-md-3 col-sm-4 col-6">
                            <select class="form-select" name="department" id="department">
                                <option selected disabled>Select Department</option>
                                @foreach ($department_details as $d)
                                    <option value="{{ $d->id }}">{{ $d->name }}</option>

                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                    <table class="table"  id="SortlistedApplicants" >
                        <thead>
                            <tr>
                                <th>Applicants</th>
                                <th>Rank</th>
                                <th>Score</th>
                                <th>Nation</th>
                                <th>Email</th>
                                <th>Contact</th>
                                <th>Position	</th>
                                <th>Department</th>
                            </tr>
                        </thead>


                    </table>

                </div>
            </div>
        </div>

    </div>
    <input type="hidden" name="{{     $id }}" value="{{$id }}" id="RequestedID" >
    @endsection

@section('import-css')

@endsection

@section('import-scripts')
<script>

$(document).ready(function() {



    $("#department").select2({
        "placeholder":"Select Department "
    });
    $('.Search').on('keyup', function() {
        SortlistedList();
    });

    $("#department").on('change', function() {
        SortlistedList();
    });
    SortlistedList()

});

function  SortlistedList()
    {
        $('#SortlistedApplicants tbody').empty();
        if ($.fn.DataTable.isDataTable('#SortlistedApplicants'))
        {
        $('#SortlistedApplicants').DataTable().destroy();
        }
            var SortlistedApplicants = $('#SortlistedApplicants').DataTable({
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
                    url: (function() {
                        let id = $("#RequestedID").val();
                        let url = "{{ route('resort.ta.shortlisted', ':id') }}";
                        return url.replace(':id', id);
                    })(),
                    type: 'GET',
                    data: function(d) {

                                // var complianceStatus = $('#complianceSelect').val();
                                // d.compliance_status = complianceStatus;
                                let Department = $('#department').val();
                                var searchTerm = $('.Search').val();
                                d.searchTerm = searchTerm;
                                d.Department= Department;


                            }
                },
                columns: [

                    { data: 'Applicants', name: 'Applicants', className: 'text-nowrap' },
                    { data: 'Rank', name: 'Rank', className: 'text-nowrap' },
                    { data: 'Score', name: 'Score', className: 'text-nowrap' },
                    { data: 'Nation', name: 'Nation', className: 'text-nowrap' },
                    { data: 'Email', name: 'Email', className: 'text-nowrap' },
                    { data: 'Contact', name: 'Contact', className: 'text-nowrap' },
                    { data: 'Position', name: 'Position', className: 'text-nowrap' },
                    { data: 'Department', name: 'Department', className: 'text-nowrap' },

                ]
        });
    }

</script>
@endsection

