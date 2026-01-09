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
                    <!-- <div class="col-auto">
                        <div class="d-flex justify-content-end">
                            <a href="#" class="btn btn-theme">Request Manning</a>
                        </div>
                    </div> -->
                </div>
            </div>


            <div>
                <div class="card">
                    <div class="card-header">
                        <div class="row g-md-3 g-2 align-items-center">
                            <div class="col-xl-3 col-lg-5 col-md-7 col-sm-8 ">
                                <div class="input-group">
                                    <input type="search" class="form-control search" placeholder="Search" />
                                    <i class="fa-solid fa-search"></i>
                                </div>
                            </div>
                            {{-- <div class="col-xl-2 col-md-3 col-sm-4 col-6">
                                <select class="form-select">
                                    <option selected>All Vacancy</option>
                                    <option value="1">abc</option>
                                    <option value="2">abc</option>
                                </select>
                            </div> --}}
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
                            <div class="col-auto ms-auto">
                                <a href="#" class="btn btn-grid"><img src="{{ URL::asset('resorts_assets/images/grid.svg')}}" alt="icon"></a>
                                <a href="#" class="btn btn-list active"><img src="{{ URL::asset('resorts_assets/images/list')}}.svg"
                                        alt="icon"></a>
                            </div>
                        </div>
                    </div>
                    <div class="list-main d-block">
                        <table class="table w-100" id="VacanciesApplcation">
                            <thead>
                                <tr>
                                    <th>Positions</th>
                                    <th>Department</th>
                                    <th>No. of position</th>
                                    <th>Applicant</th>
                                    <th>Application Date</th>
                                    <th>Expiry Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>


                        </table>
                    </div>
                    <div class="grid-main d-none" id="vacanciesGrid">




                    </div>
                    <div id="paginationLinks"></div>


                </div>
            </div>

        </div>
    </div>

    <div class="modal fade" id="jobAD-modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-small modal-jobAD">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="staticBackdropLabel">Job Advertisement</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="jobAD-form">
                    @csrf
                <div class="modal-body ">
                    <div class="input-group mb-sm-4 mb-3">
                        <input type="text" class="form-control "  name="link_Expiry_date" id="link_Expiry_date" placeholder="Expiry Date" />
                    </div>
                    <div class="text-center mb-sm-3 mb-2 ">
                        <input type="hidden" class="form-control ApplicationId" name="ApplicationId"  placeholder="Job Advertisement Link" />
                        <input type="hidden" class="form-control extendata" name="ExtendFlag" value="extendata"/>

                        <a href="javascript:void(0)" target="blank" class="a-link AppendJobAdvLink"></a>
                    </div>
                </div>
                <div class="modal-footer justify-content-center">
                    <a href="#" data-bs-dismiss="modal" class="btn btn-themeGray ms-auto">Cancel</a>
                    <button  class="btn btn-theme">Submit</button>
                </div>

            </div>
            </form>
        </div>
    </div>
    @endsection

@section('import-css')
@endsection

@section('import-scripts')
<script>

$(document).ready(function() {

    $("#ResortDepartment").select2({"Placeholder":"Select Department"});
    $(".Positions").select2({"Placeholder":"Select Positions"});
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
            });
            $(".btn-list").click(function () {
                $(this).addClass("active");
                $(".list-main").addClass("d-block");
                $(".list-main").removeClass("d-none");
                $(".btn-grid").removeClass("active");
                $(".grid-main").addClass("d-none");
                $(".grid-main").addClass("d-block");
            });


        $('#link_Expiry_date').datepicker({
            format: 'dd/mm/yyyy',
            autoclose: true,
            todayHighlight: true
        });


    $(document).on('click',".ExtendJobLink",function(){

        let ExpiryDate =  $(this).attr('data-ExpiryDate');
        let ApplicationId =  $(this).attr('data-ApplicationId');
        $("#jobAD-modal").modal('show');
        $("#ApplicationId").val(ApplicationId);
        $(".ApplicationId").val(ApplicationId);

    let parts = ExpiryDate.split('-');


    let formattedDate = parts[0] + "/" + parts[1] + "/" + parts[2];
        $('#link_Expiry_date').datepicker('setDate',ExpiryDate);
    });

        $('#jobAD-form').validate({
            rules: {
                link_Expiry_date: {
                    required: true,
                }
            },
            messages :
            {
                link_Expiry_date: {
                    required: "Please Select Expiry Date.",
                }
            },
            submitHandler: function(form) {

                var formData = new FormData(form);


                $.ajax({
                    url: "{{ route('resort.ta.GenrateAdvLink') }}",
                    type: "POST",
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        $('#respond-rejectModal').modal('hide');
                        if (response.success)
                        {
                            girdview = $(".btn-grid").hasClass('active');
                            if(girdview)
                            {
                                DatatableGrid();
                            }
                            else
                            {
                                datatablelist();
                            }
                            $("#jobAD-modal").modal('hide');

                               toastr.success(response.message, "Success", {
                                    positionClass: 'toast-bottom-right'
                                });


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
        });


        checkGrid();
        $(".btn-grid").on('click', function () {

            checkGrid();
        });
        function checkGrid()
        {
            let girdview = $(".btn-grid").hasClass('active');
            if(girdview)
            {
                DatatableGrid();
            }
            else
            {
                datatablelist();
            }


        }


        function datatablelist()
        {
            if ($.fn.DataTable.isDataTable('#VacanciesApplcation')) {
                $('#VacanciesApplcation').DataTable().destroy();
            }
            var VacanciesApplcation = $('#VacanciesApplcation').DataTable({
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
                    url: '{{ route("resort.vacancies.FreshApplicant") }}',
                    type: 'GET',
                    data: function(d) {
                            // var complianceStatus = $('#complianceSelect').val();
                            // d.compliance_status = complianceStatus;
                            let Department = $('#ResortDepartment').val();
                            let Positions = $('.Positions').val();
                            var searchTerm = $('.search').val();
                            d.searchTerm = searchTerm;
                            d.Department= Department;
                            d.Positions =  Positions;

                        }
                },
                columns: [
                    { data: 'Department', name: 'Department', className: 'text-nowrap' },
                    { data: 'Position', name: 'Position', className: 'text-nowrap' },
                    {data:'NoOfVacnacy',name:'EmployeeType',className:'text-nowrap'},
                { data: 'NoOfApplication', name: 'Question', className: 'text-nowrap' },
                    { data: 'ApplicationDate', name: 'action', className: 'text-nowrap'},
                    { data: 'ExpiryDate', name: 'ReportingTo', className: 'text-nowrap'},
                    { data: 'action', name: 'action', orderable: false, searchable: false },
                ]
            });
        }

        function DatatableGrid()
        {

                let Department = $('#ResortDepartment').val();
                let Positions = $('.Positions').val();
                let searchTerm = $('.search').val();
                $.ajax({
                url: "{{route('ta.vacancies.GirdData')}}", // Replace with the correct route
                type: "GET",
                data: {
                        Department:Department,
                        Positions:Positions,
                        searchTerm: searchTerm  // Example: send additional data like search term
                    },
                    success: function(response) {
                        let data = response.view; // Array of vacancies

                        // Empty the grid before adding new data
                        $('#vacanciesGrid').html(data);
                        $('#paginationLinks').html(response.pagination);
                    },
                    error: function() {
                        alert("Error loading vacancies.");
                    }
                });
        }


        $('.search').on('keyup', function() {

            let girdview = $(".btn-grid").hasClass('active');
            if(girdview)
            {
                DatatableGrid();
            }
            else
            {
                datatablelist();
            }


        });

        $('.Positions').on("change",function(){
            let girdview = $(".btn-grid").hasClass('active');
            if(girdview)
            {
                DatatableGrid();
            }
            else
            {
                datatablelist();
            }
        });


});
</script>
@endsection

