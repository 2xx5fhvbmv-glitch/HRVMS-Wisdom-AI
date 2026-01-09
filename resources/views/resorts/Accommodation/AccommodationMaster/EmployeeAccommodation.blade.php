

@extends('resorts.layouts.app')
@section('page_tab_title', $page_title)

@if ($message = Session::get('success'))
    <div class="alert alert-success">
        <p>{{ $message }}</p>
    </div>
@endif

@section('content')

<div class="body-wrapper pb-5">
    <div class="container-fluid">
        <div class="page-hedding page-appHedding">
            <div class="row justify-content-between g-md-2 g-1">
                <div class="col-auto">
                    <div class="page-title">
                        <span>Accommodation</span>
                        <h1>{{ $page_title }}</h1>
                    </div>
                </div>
            </div>
        </div>


        <div class="card">
            <div class="card-header">
                <div class="row g-md-3 g-2 align-items-center">
                    <div class="col-xl-3 col-lg-5 col-md-7 col-sm-8 ">
                        <div class="input-group">
                            <input type="search" class="form-control Search" placeholder="Search" />
                            <i class="fa-solid fa-search"></i>
                        </div>
                    </div>
                    <div class="col-xl-2 col-md-3 col-sm-4 col-6">
                        <select class="form-select Department">
                            <option ></option>
                            @foreach ($ResortDepartment as $r)
                                <option value="{{$r->id}}">{{$r->name}}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-xl-2 col-md-3 col-sm-4 col-6">
                        <select class="form-select" id="position">
                            <option></option>
                        </select>
                    </div>
                    <div class="col-auto ms-auto">
                        <a href="#" class="btn btn-list active "><img src="{{ URL::asset('resorts_assets/images/list.svg') }}" alt="icon"></a>
                        <a href="#" class="btn btn-grid "><img src="{{ URL::asset('resorts_assets/images/grid.svg') }}" alt="icon"></a>

                    </div>
                </div>
            </div>
            <div class="list-main d-block">
                <div class="table-responsive">
                    <table class="table table-ta-employeesAccommodationlist " width="100%">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Position</th>
                                <th>Department</th>
                                <th>Since</th>
                                <th>Building</th>
                                <th>Floor No</th>
                                <th>Room No</th>
                                <th>Room Type</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>

                        </tbody>
                    </table>
                </div>
            </div>
            <div class="grid-main  d-none ">
                <div class="row g-md-4 g-3 mb-4 grid-mainlist">
                    @if($data->isNotEmpty())
                        @foreach ($data as $d)
                            <div class="col-xxl-cust5 col-xl-3 col-lg-4 col-sm-6" >

                                <div class="empAccomGrid-block">
                                    <div class="img-circle"><img src="{{ $d->profileImg }}" alt="image"></div>
                                    <h6>{{ $d->EmployeeName }}</h6>
                                    <span class="badge badge-themeNew">{{ $d->Emp_id }}</span>
                                    <p>{{ $d->position_title }}</p>
                                    <div class="position">{{ $d->DepartmentName }}</div>
                                    <div class="bg">

                                        @if($d->effected_date)
                                        Since {{ $d->effected_date}}

                                    @endif
                                    </div>
                                    <table>
                                        <tbody>
                                            <tr>
                                                <th>Type:</th>
                                                <td>Room In Building</td>
                                            </tr>
                                            <tr>
                                                <th>Location:</th>
                                                <td>{{ $d->BName }}</td>
                                            </tr>
                                            <tr>
                                                <th>Floor:</th>
                                                <td>{{ $d->Floor }}</td>
                                            </tr>
                                            <tr>
                                                <th>Room No.</th>
                                                <td>{{ $d->Room }}</td>
                                            </tr>
                                            <tr>
                                                <th>Room Type:</th>
                                                <td>{{ $d->AccommodationName }}
                                                    {{-- <div class="user-ovImg">
                                                        <div class="img-circle">
                                                            <img src="{{ URL::asset('resorts_assets/images/user-2') }}.svg" alt="image">
                                                        </div>
                                                        <div class="img-circle">
                                                            <img src="{{ URL::asset('resorts_assets/images/user-3') }}.svg" alt="image">
                                                        </div>
                                                    </div> --}}
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>

                                    <div>
                                        <a target="_blank" href="{{ route('resort.accommodation.AccommodationEmployeeDetails',base64_encode($d->employee_id)) }}" class="btn btn-themeSkyblue btn-sm">View Details</a>
                                    </div>
                                </div>

                            </div>
                        @endforeach
                    @endif

                </div>
                <div class="pagination-custom">
                    <nav aria-label="Page navigation example">
                        {!! $data->appends(['view' => request('view', 'grid')])->links('pagination::bootstrap-4') !!}

                    </nav>
                </div>
            </div>

        </div>


    </div>
</div>


@endsection

@section('import-css')
@endsection

@section('import-scripts')
<script>

    $(document).ready(function () {

        $("#position").select2({
            placeholder: "Position",
            allowClear: true
        });
        $(".Department").select2({
            placeholder: "Department",
            allowClear: true
        });
        const urlParams = new URLSearchParams(window.location.search);

        const currentView = urlParams.get('view')  // Default to 'list'
        // Initialize the view on page load
        setView(currentView);
        datatablelist();
    });

    function setView(view)
    {
        if(view=="grid")
        {
            $(".btn-grid").trigger("click");
        }
        if(view=="list")
        {
            $(".btn-list").trigger("click");
        }
    }
    $(document).on('change', '.Department', function() {
        var deptId = $(this).val();
        $.ajax({
            url: "{{ route('resort.ta.PositionSections') }}",
            type: "post",
            data: {
                deptId: deptId
            },
            success: function(d) {
                // Clear the dropdown and add a placeholder option


                if (d.success == true) {

                    let string = '<option></option>';
                    $.each(d.data.ResortPosition, function(key, value) {
                        string += '<option value="' + value.id + '">' + value
                            .position_title + '</option>';
                    });
                    $("#position").html(string);

                    let string1 = '<option></option>';
                    $.each(d.data.ResortSection, function(key, value) {
                        string1 += '<option value="' + value.id + '">' + value
                            .name + '</option>';
                    });
                    $(".Section").html(string1);

                }
            },
            error: function(response) {
                toastr.error("Position Not Found", {
                    positionClass: 'toast-bottom-right'
                });
            }
        });
    });

    $(".btn-grid").click(function () {
        $(this).addClass("active");
        $(".grid-main").addClass("d-block");
        $(".grid-main").removeClass("d-none");
        $(".btn-list").removeClass("active");
        $(".list-main").addClass("d-none");
        $(".list-main").removeClass("d-block");
        datatablelist()
    });
    $(".btn-list").click(function () {
        $(this).addClass("active");
        $(".list-main").addClass("d-block");
        $(".list-main").removeClass("d-none");
        $(".btn-grid").removeClass("active");
        $(".grid-main").addClass("d-none");
        $(".grid-main").addClass("d-block");
        $('.table-ta-employeeslist').DataTable().ajax.reload();
    });

    $(document).on('keyup', '.Search', function () {
        EmployeeGrid();
        datatablelist()
    });

    $(document).on('change', '#position', function () {
        EmployeeGrid();
        datatablelist()
    });

    function EmployeeGrid()
    {
        var search = $(".Search").val();
        var Poitions = $("#position").val();
        $.ajax({
                url: "{{ route('resort.accommodation.SearchEmpAccommodationgird') }}",
                type: "get",
                data: {"_token":"{{ csrf_token() }}","search":search,"Poitions":Poitions},
                success: function (response) {

                    if (response.success)
                    {

                        $(".grid-mainlist").html(response.view);

                    } else {
                        toastr.error(response.message, "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                },
                error: function(response) {
                    var errors = response.responseJSON;
                    var errs = '';
                    $.each(errors.errors, function(key, error) { // Adjust according to your response format
                        errs += error + '<br>';
                    });
                    toastr.error(errs, { positionClass: 'toast-bottom-right' });
                }
            });

    }
    function datatablelist()
    {
        if ($.fn.DataTable.isDataTable('.table-ta-employeesAccommodationlist'))
        {
            $('.table-ta-employeesAccommodationlist').DataTable().destroy();
        }

        var divisionTable = $('.table-ta-employeesAccommodationlist').DataTable({
                searching: false,
                bLengthChange: false,
                bFilter: true,
                bInfo: true,
                bAutoWidth: false,
                scrollX: true,
                iDisplayLength: 6,
                processing: true,
                serverSide: true,
                order: [[9, 'desc']],
                ajax: {
                    url: "{{ route('resort.accommodation.EmpAccommodationList') }}",
                    type: 'GET',
                    data: function(d) {
                        d.position = $("#position").val();
                        d.searchTerm = $('.Search').val();
                    }
                },
                columns: [
                    { data: 'Name', name: 'Name',},
                    { data: 'Position', name: 'Position' },
                    { data: 'Department', name: 'Department' },
                    { data: 'Since', name: 'Since' },
                    { data: 'Building', name: 'Building'},
                    { data: 'FloorNo', name: 'FloorNo' },
                    { data: 'RoomNo', name: 'RoomNo' },
                    { data: 'RoomType', name: 'RoomType' },
                    { data: 'Action', name: 'Action' },
                     {data:'created_at',visible:false,searchable:false},

                ]
            });
    }
</script>
@endsection
