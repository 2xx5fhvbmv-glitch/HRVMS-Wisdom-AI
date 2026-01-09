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
            <div class="page-hedding page-appHedding">
                <div class="row justify-content-between g-md-2 g-1">
                    <div class="col-auto">
                        <div class="page-title">
                            <span>Talent Acquisition</span>
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
                                <input type="search" class="form-control search" placeholder="Search">
                                <i class="fa-solid fa-search"></i>
                            </div>
                        </div>
                        <div class="col-xl-2 col-md-3 col-sm-4 col-6">
                            <select class="form-select" name="Department" id="ResortDepartment">
                                <option selected disabled>Select Department</option>
                                @if($ResortDepartment->isNotEmpty())
                                    @foreach ($ResortDepartment as $item)
                                        <option value="{{ $item->id }}" data-name="{{ $item->name }}">{{ $item->name }}</option>

                                    @endforeach

                                @endif
                            </select>
                        </div>
                        <div class="col-xl-2 col-md-3 col-sm-4 col-6">
                            <select class="form-select  Positions" name="Positions">
                                <option selected disabled>Select Poitions</option>
                           </select>
                        </div>
                        {{-- <div class="col-xl-2 col-md-3 col-sm-4 col-6">
                            <input type="text" class="form-control" placeholder="18/10/2024">
                        </div> --}}
                        <div class="col-auto ms-auto">
                            <a href="javascript:void(0)" class="btn btn-grid"><img src="{{URL::asset('resorts_assets/images/grid.svg')}}" alt="icon"></a>
                            <a href="javascript:void(0)" class="btn btn-list active"><img src="{{ URL::asset('resorts_assets/images/list.svg')}}" alt="icon"></a>
                        </div>
                    </div>
                </div>
                <div class="list-main ">
                    <div class="table-responsive">
                        <table class="table table-collapseNew TalentPool">
                            <thead>
                                <tr>
                                    <th>Applicants<i class="fa-solid fa-caret-down"></i></th>
                                    <th>Position</th>
                                    <th>Department</th>
                                    <th>Nationality<i class="fa-solid fa-caret-up"></i></th>
                                    <th>Passport No.</th>
                                    <th>Experience<i class="fa-solid fa-caret-up"></i></th>
                                    <th>Email</th>
                                    <th>Contact</th>
                                    <th>Applied Date<i class="fa-solid fa-caret-down"></i></th>
                                    <th>Stage<i class="fa-solid fa-caret-down"></i></th>
                                    <th>Consent Expiry Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>

                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="grid-main d-none  ">
                    <div class="row g-md-4 g-3 mb-4 " id="grid_main_view">

                    </div>

                </div>

            </div>


        </div>
    </div>
    @endsection
    <div class="modal fade" id="Response-modal" tabindex="-1" aria-labelledby="exampleModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-small">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="staticBackdropLabel">Reason </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id='RevertResponeForm'>
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <input type="hidden" name="applicant_status_id" id="applicant_status_id">
                        <textarea  class="form-control" readonly disabled id="Reason" name="Reason" placeholder="Reason">

                        </textarea>
                    </div>
                    <div style="height:180px;"></div>
                    <input type="hidden" name="Interview_id" id="Interview_id">
                </div>
                <div class="modal-footer">
                    <a href="#" data-bs-dismiss="modal" class="btn btn-themeGray ms-auto">Cancel</a>
                    <button type="submit" class="btn btn-themeBlue RevertBack d-none">Revert Back</button>
                </div>
            </form>
        </div>
    </div>
</div>
@section('import-css')
@endsection

@section('import-scripts')
<script>
    $(document).ready(function () {

        $('.search').on('keyup', function() {

            let girdview = $(".btn-grid").hasClass('active');

            if(girdview)
            {
                DatatableGrid();

            }
            else
            {
                DatatableList();
            }
        });
        $("#ResortDepartment").select2({"Placeholder":"Select Department"});
        $(".Positions").select2({"Placeholder":"Select Positions"});

        $(document).on("change", ".Positions", function() {
            let girdview = $(".btn-grid").hasClass('active');

                    if(girdview)
                    {
                        DatatableGrid();

                    }
                    else
                    {
                        DatatableList();
                    }
     });
        $(document).on('change', '#ResortDepartment', function() {
                var deptId = $(this).val();

                let currentDepartment = $(this).val();
                let isDuplicate = false;

                let string='<option selected disabled>Select Positions</option>';
                $(".Positions").html(string);
                    $.ajax({
                        url: "{{ route('resort.get.position') }}",
                        type: "post",
                        data: {
                            deptId: deptId
                        },
                        success: function(data) {
                            if(data.success == true)
                            {
                                $.each(data.data, function(key, value) {
                                    console.log(value.position_title);
                                    string+='<option value="'+value.id+'">'+value.position_title+'</option>';
                                });
                                $(".Positions").html(string);
                            }
                        },
                        error: function(response) {
                            toastr.error("Position Not Found", { positionClass: 'toast-bottom-right' });
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
                DatatableGrid()
            });
            $(".btn-list").click(function () {
                $(this).addClass("active");
                $(".list-main").addClass("d-block");
                $(".list-main").removeClass("d-none");
                $(".btn-grid").removeClass("active");
                $(".grid-main").addClass("d-none");
                $(".grid-main").addClass("d-block");
                $('.TalentPool').DataTable().ajax.reload();
            });
            DatatableList();
            $('#RevertResponeForm').validate({
                rules: {
                    Reason: {
                        required: true,
                    },
                    applicant_status_id: {
                        required: true,
                    }
                },
                messages :
                {
                    Reason: {
                        required: "Reason is not exitest.",
                    }
                    ,
                    applicant_status_id: {
                        required:  "Reason Applicant Status Not Found.",
                    }
                },
                submitHandler: function(form) {
                    var formData = new FormData(form);

                    $.ajax({
                        url: "{{ route('resort.ta.RevertBack') }}",
                        type: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            $('#respond-rejectModal').modal('hide');
                            if (response.success)
                            {
                                DatatableList(); DatatableGrid();
                                toastr.success(response.message, "Success",
                                        {
                                            positionClass: 'toast-bottom-right'
                                        });
                                        $("#Response-modal").modal('hide');

                            }

                    },
                        error: function(response) {
                            var errors = response.responseJSON;
                            var errs = '';
                            console.log(errors.errors);
                            $.each(errors.errors, function(key, error)
                            {
                                console.log(error);
                                errs += error + '<br>';
                            });
                            toastr.error(errs, { positionClass: 'toast-bottom-right'});
                        }
                    });
                }
            });
        });
    $(document).on("click", ".destoryApplicant", function() {
            var base64_id = $(this).attr('data-id');
            var location = $(this).attr('data-location');

            // SweetAlert confirmation dialog
            Swal.fire({
                title: "Are you sure?",
                text: "This action will permanently delete the applicant.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Yes, delete it!"
            }).then((result) => {
                if (result.isConfirmed) {
                    // Proceed with AJAX request after confirmation
                    $.ajax({
                        url: "{{ route('resort.ta.destoryApplicant') }}",
                        type: "POST",
                        data: { base64_id: base64_id, "_token": "{{ csrf_token() }}" },
                        success: function(response) {
                            $('#respond-rejectModal').modal('hide');
                            if (response.success) {
                                Swal.fire(
                                    "Deleted!",
                                    response.message,
                                    "success"
                                );
                                $("#talentPool_" + location).remove();
                                DatatableList(); DatatableGrid();
                            } else {
                                Swal.fire(
                                    "Error!",
                                    response.message,
                                    "error"
                                );
                            }
                        },
                        error: function(response) {
                            var errors = response.responseJSON;
                            var errs = '';
                            $.each(errors.errors, function(key, error) { // Adjust according to your response format
                                console.log(error);
                                errs += error + '<br>';
                            });
                            Swal.fire("Error!", errs, "error");
                        }
                    });
                }
            });
        });

        $(document).on("click", ".RejactionReason", function() {
        let Comments = $(this).attr('data-Comments');
        let Rank = $(this).attr('data-Rank');
        let applicant_status_id = $(this).attr('data-applicant_status_id');


        $("#Reason").val(Comments);
        if(Rank == 0)
        {
            $(".RevertBack").removeClass('d-none');
            $("#applicant_status_id").val(applicant_status_id);
        }

        $("#Response-modal").modal('show');
    });
    $(document).on("click", ".destoryApplicant", function() {
            var base64_id = $(this).attr('data-id');
            var location = $(this).attr('data-location');

            // SweetAlert confirmation dialog
            Swal.fire({
                title: "Are you sure?",
                text: "This action will permanently delete the applicant.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Yes, delete it!"
            }).then((result) => {
                if (result.isConfirmed) {
                    // Proceed with AJAX request after confirmation
                    $.ajax({
                        url: "{{ route('resort.ta.destoryApplicant') }}",
                        type: "POST",
                        data: { base64_id: base64_id, "_token": "{{ csrf_token() }}" },
                        success: function(response) {
                            $('#respond-rejectModal').modal('hide');
                            if (response.success) {
                                Swal.fire(
                                    "Deleted!",
                                    response.message,
                                    "success"
                                );
                                $("#talentPool_" + location).remove();
                                DatatableList(); DatatableGrid();
                            } else {
                                Swal.fire(
                                    "Error!",
                                    response.message,
                                    "error"
                                );
                            }
                        },
                        error: function(response) {
                            var errors = response.responseJSON;
                            var errs = '';
                            $.each(errors.errors, function(key, error) { // Adjust according to your response format
                                console.log(error);
                                errs += error + '<br>';
                            });
                            Swal.fire("Error!", errs, "error");
                        }
                    });
                }
            });
        });



    function DatatableList()
    {
            if ($.fn.DataTable.isDataTable('.TalentPool'))
            {
                $('.TalentPool').DataTable().destroy();
            }
            var TalentPool = $('.TalentPool').DataTable({
                    searching: false,
                    bLengthChange: false,
                    bFilter: true,
                    bInfo: true,
                    bAutoWidth: false,
                    scrollX: true,
                    iDisplayLength: 6,
                    processing: true,
                    serverSide: true,
                    order:[[12, 'desc']],
                    ajax: {
                        url: "{{ route('resort.ta.TalentPool')}}",
                        type: 'GET',
                        data: function(d) {
                            d.ResortDepartment = $("#ResortDepartment").val();
                            d.searchTerm = $('.search').val();
                            d.Positions  = $('.Positions ').val()
                        }
                    },
                    columns: [
                        { data: 'first_name', name: 'first_name', render: function (data, type, row) {
                            return `<div class="tableUser-block">
                                <div class="img-circle"><img src="${row.profileImg}" alt="user"></div>
                                <span class="userApplicants-btn" data-id="${row.applicant_id}">${row.name}</span>
                            </div>`;
                        }},

                        { data: 'Position', name: 'Position' },
                        { data: 'Department', name: 'Department' },
                        { data: 'countryName', name: 'countryName' },
                        { data: 'passport_no', name: 'passport_no' },
                        { data: 'total_work_exp', name: 'total_work_exp', render: function (data) {
                            return `${data} year`;
                        }},
                        { data: 'email', name: 'email' },
                        { data: 'contact', name: 'contact' },
                        { data: 'Application_date', name: 'Application_date' },
                        { data: 'Stage', name: 'Stage'},
                        { data: 'ConsentExpiryDate', name: 'ConsentExpiryDate'},
                        { data: 'action', name: 'action', orderable: false, searchable: false },
                        {data:'created_at', visible:false,searchable:false},
                    ]

                });



    }
    function DatatableGrid()
    {


                        $.ajax({
                            url:"{{ route('resort.ta.getTalentPoolApplicant') }}",
                            type: "post",
                            data:
                            {
                                id :$("#vacancy-id").val(),
                                searchTerm : $('.search').val(),
                                ResortDepartment: $("#ResortDepartment").val(),
                                searchTerm: $('.search').val(),
                                Positions : $('.Positions ').val()
                            },
                            success: function(response)
                            {
                                if (response.success)
                                {
                                    $("#grid_main_view").html(response.view);
                                    // $('.table-applicants').DataTable().ajax.reload();
                                    ApplicantProgress();
                                }
                            },
                                error: function(response) {
                                    var errors = response.responseJSON;
                                    var errs = '';
                                    console.log(errors.errors);
                                    $.each(errors.errors, function(key, error)
                                    {
                                        console.log(error);
                                        errs += error + '<br>';
                                    });
                                    toastr.error(errs, { positionClass: 'toast-bottom-right'});
                                }
                        });
    }

</script>
@endsection

