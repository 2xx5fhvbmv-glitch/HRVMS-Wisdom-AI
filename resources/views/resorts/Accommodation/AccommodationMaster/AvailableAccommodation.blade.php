

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
        <div class="page-hedding">
            <div class="row justify-content-between g-3">
                <div class="col-auto">
                    <div class="page-title">
                        <span>Accommodation</span>
                        <h1>{{ $page_title }}</h1>
                    </div>
                </div>
            </div>
        </div>

        <div>
            <div class="card">
                <div class="card-header">
                    <div class="row g-md-3 g-2 align-items-center">
                        {{-- <div class="col-xl-3 col-lg-4 col-md-5  col-sm-6 ">
                            <input type="text" class="form-control datepicker" placeholder="Select Duration">
                        </div> --}}
                        <div class="col-xl-3 col-lg-4 col-md-5  col-sm-6">
                            <select class="form-select  beds">
                                <option selected value="all">Available  beds</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="accordion-gary" id="accordionAccommodation">
                    @if($BuildingModel->isNotEmpty())
                        @foreach ($BuildingModel as $b)
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingOne">
                                    <button class="accordion-button GetInternalDetails" data-id="{{ $b->id }}"type="button" data-bs-toggle="collapse"
                                        data-bs-target="#collapseOne{{ $b->id }}" aria-expanded="true" aria-controls="collapseOne">
                                        {{ $b->BuildingName }}
                                        <span>Room Capacity: {{ $b->TotalRoom }}   | Rooms Available: {{ $b->AvailableRooms }} | Bed Capacity: {{ $b->BedCapacity }} | Beds Available:
                                            {{ $b->AvailableBed }}</span>
                                    </button>
                                </h2>
                                <div id="collapseOne{{ $b->id }}" class="accordion-collapse collapse show" aria-labelledby="headingOne"
                                    data-bs-parent="#accordionAccommodation">
                                    <div class="bg-white">
                                        <div class="table-responsive">
                                            <table class="table table-accommodation" id="table-accommodation_{{ $b->id }}">
                                                <thead>
                                                    <tr>
                                                        <th>Floor No.</th>
                                                        <th>Room No.</th>
                                                        <th>For</th>
                                                        <th>Bed Capacity</th>
                                                        <th>Employee category</th>
                                                        <th>Room facilities</th>
                                                        <th>Status</th>
                                                        <th>Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody>


                                                </tbody>
                                            </table>
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
</div>

<div class="modal fade" id="selectBed-modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-small modal-selectBed">
        <div class="modal-content">
        <form id="AssignBedForm" data-parsley-validate>
            @csrf      

            <div class="modal-header">
                <h5 class="modal-title" id="staticBackdropLabel">Select Bed</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="bg-themeGrayLight">
                <div class="row">
                    <div class="col-12">

                        <select class="form-select" name="emp_id" id="EmployeeList"   
                            required 
                            data-parsley-required-message="Please select Employee." 
                            data-parsley-errors-container="#Type_employee_error_0_1">
                            <option selected>Select Employee</option>
                        </select>
                        <div id="Type_employee_error_0_1"></div>

                    </div>

                </div>
                <br>
                <div class="row">

                    <div class="col-12">
                            <div class="bed-badge">Bed</div>
                                <div class="row g-3 AppnedBed">

                                </div>
                                <input type="hidden" name="assignId" id="assignId" required data-parsley-errors-container="#assignIdError" data-parsley-required-message="Please select a bed.">
                                <div id="assignIdError" class="text-danger small mt-1"></div>
                            </div>
                    </div>
                </div>

            </div>
            <div class="modal-footer">
                 <a href="javascript:void(0)" data-bs-dismiss="modal" class="btn btn-themeGray ms-auto">Cancel</a>
                <button type="submit"
                    class="btn btn-themeBlue">Submit</button>
            </div>
        </form>
        </div>
    </div>
</div>
<div class="modal fade" id="reviewDetails-modal" tabindex="-1" aria-labelledby="exampleModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-small card-reviewDetails">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="staticBackdropLabel">Review Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body appendhereAfterAssign">


            </div>
            <div class="modal-footer">
                <!-- <a href="#" data-bs-dismiss="modal" class="btn btn-themeGray ms-auto">Cancel</a> -->
                <a href="#" data-bs-toggle="modal" data-bs-dismiss="modal" class="btn btn-themeBlue">Submit</a>
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
        $('#AssignBedForm').parsley().validate();
        $("#EmployeeList").select2({
            placeholder: "Select Employee",
            allowClear: true,
        });
        $('#AssignBedForm').validate({
            rules: {
                assignId: {
                    required: true,
                },
                emp_id: {
                    required: true,
                }
            },
            messages: {
                assignId: {
                    required: "Please Select Bed.",
                },
                emp_id: {
                    required: "Please Select Employee.",
                }
            },
            errorPlacement: function (error, element) {
                if (element.attr("name") === "assignId") {
                $('.bed-block .assign-error').remove();

                // Add error message after the selected bed-block's paragraph
                if($('.bed-block.selected').length) {
                    $('.bed-block.selected').append(
                        `<div class="assign-error" style="color: red; margin-top: 5px;">${error.text()}</div>`
                    );
                } else {
                    // If no bed is selected, append to all bed-blocks
                    $('.bed-block').append(
                        `<div class="assign-error" style="color: red; margin-top: 5px;">${error.text()}</div>`
                    );
                }
            } else {
                // Default placement for other fields
                error.insertAfter(element);
            }
            },
            submitHandler: function (form, event) {
                event.preventDefault(); // Prevent form default submission

                var formData = new FormData(form);

                $.ajax({
                    url: "{{ route('resort.accommodation.AssignAccommodationToEmp') }}",
                    type: "POST",
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function (response) {
                        if (response.success) {
                            var employee = response.data.employee;
                            var accommodation = response.data.accommodation;
                            var facilities = accommodation.facilities.join(", ");
                            $("#selectBed-modal").modal('hide');

                            $(".appendhereAfterAssign").html(`
                                <div class="empDetails-user">
                                     <div class="img-circle"><img src="${employee.profile_picture}" alt="user">
                                    </div>
                                    <div>
                                        <h4>${employee.name}<span class="badge badge-themeNew">#34523</span></h4>
                                        <p>${employee.position}</p>
                                    </div>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-lable">
                                        <tr>
                                            <th>Building:</th>
                                            <td>${accommodation.building_name}</td>
                                        </tr>
                                        <tr>
                                            <th>Room No.</th>
                                            <td>${accommodation.room_no}</td>
                                        </tr>
                                        <tr>
                                            <th>Room Facilities:</th>
                                            <td>${facilities}</td>
                                        </tr>
                                        <tr>
                                            <th>Room Status:</th>
                                            <td>${accommodation.RoomStatus}</td>
                                        </tr>
                                        <tr>
                                            <th>Accommodation Name:</th>
                                            <td>${accommodation.accommodation_name}</td>
                                        </tr>
                                    </table>
                                </div>
                            `);
                            $("#reviewDetails-modal").modal('show');
                            getCollapesList();
                        } else {
                            toastr.error(response.message, "Error", {
                                positionClass: "toast-bottom-right",
                            });
                        }
                    },
                    error: function (response) {
                        var errors = response.responseJSON;
                        var errs = '';
                        $.each(errors.errors, function (key, error) {
                            errs += error + '<br>';
                        });
                        toastr.error(errs, "Error", {
                            positionClass: "toast-bottom-right",
                        });
                    }
                });
            }
        });


    });
    getCollapesList();

    $(document).on("change",".beds",function(){


        getCollapesList();
    });
    $(document).on("click",".GetInternalDetails",function()
    {
        getCollapesList();
    });
    $(document).on("click",".AssingToRoom",function()
    {
        var RoomType = $(this).data('roomtype');
        var available_a_id = $(this).data('id');

        $.ajax({
            url: "{{ route('resort.accommodation.GetAccmmodationwiseEmployee') }}", // Your route for file upload
            type: "POST",
            data: {"_token":"{{ csrf_token() }}","RoomType": RoomType,"available_a_id":available_a_id},
            success: function(response)
            {
                if (response.success)
                {
                    var row = '';
                    var option = '<option></option>';
                    $.each(response.Employees, function (i, v)
                    {
                        option +=`<option value='${v.EmployeeId}'>${v.first_name}  ${v.last_name}</option>`;
                    });
                    $("#EmployeeList").html(option);
                            $.each(response.AssingAccommodation, function (i, v)
                            {

                                    row +=`<div class="col-6">
                                                <div class="bed-block" data-id='${v.id}'>
                                                    <div class="position-relative">
                                                        <img src="{{ URL::asset('resorts_assets/images/bed-active.png') }}" alt="bed">
                                                        <img src="{{ URL::asset('resorts_assets/images/check-circle-green.svg') }}" class="icon" alt="icon">
                                                        <div class="img-circle" data-bs-toggle="tooltip" data-bs-placement="bottom"
                                                            title="Christian Slatter"><img src="{{ URL::asset('resorts_assets/images/user-2.svg') }}" alt="user">
                                                        </div>
                                                    </div>
                                                    <p>Available</p>
                                                </div>
                                            </div>`;

                            });

                            $(".AppnedBed").html(row);
                            $('[data-bs-toggle="tooltip"]').tooltip();


                    $("#selectBed-modal").modal('show');

                 
                } else {
                    toastr.error(response.message, "Error", {
                        positionClass: "toast-bottom-right",
                    });
                }
            },
            error: function(response) {
                var errors = response.responseJSON;
                var errs = '';
                $.each(errors.errors, function(key, error) {
                    errs += error + '<br>';
                });
                toastr.error(errs, {
                    positionClass: 'toast-bottom-right'
                });
            }
        });
    });

    $(document).on("click", ".bed-block", function () {
        // Remove the 'active' class from all bed blocks
        $(".bed-block").removeClass("active");
        $(this).addClass("active");
        $("#assignId").val($(this).data('id'));
    });
    function getCollapesList()
    {
        $(".GetInternalDetails").each(function () {
        if (!$(this).hasClass('collapsed'))
        {
            var id = $(this).data('id');

            if ($.fn.DataTable.isDataTable('#table-accommodation_' + id)) {
                // Destroy the existing instance
                $('#table-accommodation_' + id).DataTable().destroy();
            }

            var FloorAndRoomIndex = $('#table-accommodation_'+id).DataTable({
            "searching": false,
            "bLengthChange": false,
            "bFilter": true,
            "bInfo": true,
            "bAutoWidth": false,
            "scrollX": true,
            "iDisplayLength": 6,
            order: [[8, 'desc']],
            processing: true,
            serverSide: true,
            ajax: {
                url: '{{ route("resort.accommodation.GetBuildingWiseAvailableAccData") }}',
                type: 'GET',
                data: function (d) {
                    d.id = id;
                    d.beds =$(".beds").val();
                }
            },


            columns: [
                { data: 'FloorNo', name: 'FloorNo', className: 'text-nowrap' },
                { data: 'RoomNo', name: 'RoomNo', className: 'text-nowrap' },
                { data: 'For', name: 'For', className: 'text-nowrap' },
                { data: 'BedCapacity', name: 'BedCapacity', className: 'text-nowrap' },
                { data: 'EmployeeCategory', name: 'EmployeeCategory', className: 'text-nowrap' },
                { data: 'RoomFacilities', name: 'RoomFacilities', className: 'text-nowrap' },
                { data: 'RoomStatus', name: 'RoomStatus', className: 'text-nowrap' },
                { data: 'Action', name: 'Action', className: 'text-nowrap' },
                 {data:'created_at',visible:false,searchable:false},
            ]
        });

        }
    });
    }

</script>
@endsection
