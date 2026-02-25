@extends('resorts.layouts.app')
@section('page_tab_title' , $page_title)

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
                            @if($showDeptFilter)
                            <div class="col-xl-2 col-md-3 col-sm-4 col-6">
                                <select class="form-select" name="Department" id="ResortDepartment">
                                    <option selected disabled>Select Department</option>
                                    @foreach ($ResortDepartment as $item)
                                        <option value="{{ $item->id }}" data-name="{{ $item->name }}">{{ $item->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @else
                            <input type="hidden" id="ResortDepartment" value="">
                            @endif
                            <div class="col-xl-2 col-md-3 col-sm-4 col-6">
                                <select class="form-select Positions" name="Positions">
                                     <option selected disabled>Select Positions</option>
                                     @foreach ($filterPositions as $pos)
                                        <option value="{{ $pos->id }}">{{ $pos->position_title }}</option>
                                     @endforeach
                                </select>
                            </div>
                            <div class="col-xl-2 col-md-3 col-sm-4 col-6">
                                <button class="btn btn-themeBlue btn-sm" id="clearFilter">Clear Filter</button>
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
                                    @if($canSeeAction)<th>Action</th>@endif
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

    {{-- View Job Advertisement Modal --}}
    <div class="modal fade" id="viewJobAdModal" tabindex="-1" aria-labelledby="viewJobAdModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-small modal-jobAD">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewJobAdModalLabel">Job Advertisement</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Job Advertisement for <strong id="viewJobAdPosition"></strong></p>
                    <div id="viewJobAdCarousel" class="carousel slide mb-sm-4 mb-3" data-bs-interval="false">
                        <div class="carousel-inner text-center" id="viewJobAdCarouselInner">
                        </div>
                        <button class="carousel-control-prev" type="button" data-bs-target="#viewJobAdCarousel" data-bs-slide="prev" id="viewJobAdPrevBtn" style="display:none;">
                            <span class="carousel-control-prev-icon" aria-hidden="true" style="background-color: rgba(0,0,0,0.5); border-radius: 50%; padding: 10px;"></span>
                        </button>
                        <button class="carousel-control-next" type="button" data-bs-target="#viewJobAdCarousel" data-bs-slide="next" id="viewJobAdNextBtn" style="display:none;">
                            <span class="carousel-control-next-icon" aria-hidden="true" style="background-color: rgba(0,0,0,0.5); border-radius: 50%; padding: 10px;"></span>
                        </button>
                    </div>
                    <div class="text-center mb-sm-4 mb-3">
                        <a href="javascript:void(0)" class="btn btn-themeSkyblue btn-sm viewJobAdDownload">Download</a>
                    </div>
                    <div class="mb-sm-3 mb-2" id="viewJobAdLinkContainer" style="display:none;">
                        <h6>Job Advertisement Link:</h6>
                        <a href="#" target="_blank" class="a-link" id="viewJobAdLink"></a>
                    </div>
                </div>
                <div class="modal-footer justify-content-center">
                    <a href="#" data-bs-dismiss="modal" class="btn btn-themeGray">Close</a>
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
                        <input type="hidden" class="form-control ApplicationId" name="ApplicationId"  placeholder="test" />
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

    @if($showDeptFilter)
    $("#ResortDepartment").select2({"Placeholder":"Select Department"});
    $(document).on('change', '#ResortDepartment', function() {
        var deptId = $(this).val();
        let string = '<option selected disabled>Select Positions</option>';
        $(".Positions").html(string);
        $.ajax({
            url: "{{ route('resort.get.position') }}",
            type: "post",
            data: { deptId: deptId },
            success: function(data) {
                if(data.success == true) {
                    $.each(data.data, function(key, value) {
                        string += '<option value="'+value.id+'">'+value.position_title+'</option>';
                    });
                    $(".Positions").html(string);
                }
            },
            error: function(response) {
                toastr.error("Position Not Found", { positionClass: 'toast-bottom-right' });
            }
        });
        // Reload data on department change
        let girdview = $(".btn-grid").hasClass('active');
        if(girdview) { DatatableGrid(); } else { datatablelist(); }
    });
    @endif
    $(".Positions").select2({"Placeholder":"Select Positions"});
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

    // View Job Advertisement Modal
    $(document).on('click', '.viewJobAd', function() {
        let positionName = $(this).attr('data-position');
        let jobLink = $(this).attr('data-joblink') || '';
        let allJobImages = [];
        try {
            allJobImages = JSON.parse($(this).attr('data-alljobimages')) || [];
        } catch(e) {
            allJobImages = [];
        }

        $("#viewJobAdPosition").text(positionName);

        // Show/hide job ad link
        if (jobLink && jobLink.trim() !== '') {
            $("#viewJobAdLink").attr("href", jobLink).text(jobLink);
            $("#viewJobAdLinkContainer").show();
        } else {
            $("#viewJobAdLinkContainer").hide();
        }

        let carouselInner = $("#viewJobAdCarouselInner");
        carouselInner.empty();

        if (allJobImages.length > 0) {
            $.each(allJobImages, function(i, imgUrl) {
                let activeClass = i === 0 ? 'active' : '';
                carouselInner.append('<div class="carousel-item ' + activeClass + '"><img src="' + imgUrl + '" alt="Job Advertisement" style="max-width:100%;"></div>');
            });
            if (allJobImages.length > 1) {
                $("#viewJobAdPrevBtn, #viewJobAdNextBtn").show();
            } else {
                $("#viewJobAdPrevBtn, #viewJobAdNextBtn").hide();
            }
            $(".viewJobAdDownload").attr("data-hrefLink", allJobImages[0]).show();
        } else {
            carouselInner.append('<div class="carousel-item active"><p class="text-muted py-4">No Job Advertisement images available.</p></div>');
            $("#viewJobAdPrevBtn, #viewJobAdNextBtn").hide();
            $(".viewJobAdDownload").hide();
        }

        $("#viewJobAdModal").modal('show');
    });

    // Update download link when carousel slides
    $('#viewJobAdCarousel').on('slid.bs.carousel', function () {
        var activeImg = $(this).find('.carousel-item.active img').attr('src');
        $(".viewJobAdDownload").attr("data-hrefLink", activeImg);
    });

    // Download handler for View Job Ad modal
    $(document).on('click', '.viewJobAdDownload', function() {
        let imgUrl = $(this).attr('data-hrefLink');
        if (imgUrl) {
            let a = document.createElement('a');
            a.href = imgUrl;
            a.download = '';
            a.target = '_blank';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
        }
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
                order:[[0, 'desc']],
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
                    @if($canSeeAction)
                    { data: 'action', name: 'action', orderable: false, searchable: false },
                    @endif
                ],
                drawCallback: function() {
                    $('[data-bs-toggle="tooltip"]').tooltip();
                }
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
                        $('[data-bs-toggle="tooltip"]').tooltip();
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

        $('#clearFilter').on('click', function () {
            // Clear search input
            $('.search').val('');

            // Reset select fields (Select2-compatible)
            $('#ResortDepartment').val(null).trigger('change'); // Use null for proper reset
            $('.Positions').val(null).trigger('change');

            // Clear DataTable search box manually if needed

            // Reload your grids
            DatatableGrid();
            datatablelist();
        });
});
</script>
@endsection

