
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
                <!-- <div class="col-auto">
                    <div class="d-flex justify-content-end">
                        <a href="#" class="btn btn-theme">Request Manning</a>
                    </div>
                </div> -->
            </div>
        </div>

        <div>
            <div class="card">
                <div class="row g-md-4 g-3  mb-3">
                    <div class="col-xxl-4 col-xl-5 col-md-6">
                        <label for="select_emp" class="form-label">SELECT    EMPLOYEE</label>
                        <select class="form-select select2t-none" id="EmployeeList">

                                @if($Employeelist->isNotEmpty())
                                    @foreach ($Employeelist as $e)
                                        <option value="{{  $e->new_emp_id  }}"
                                                data-Emp_id="{{ $e->Emp_id }}"
                                                data-Position="{{ $e->Position }}"
                                                data-pic="{{ $e->profileImg }}"
                                                >{{ $e->first_name }} {{ $e->last_name }}</option>
                                    @endforeach

                                @endif
                        </select>
                    </div>
                    <div class="col-xxl-8 col-xl-7 col-md-6">
                        <div class="empDetails-user">
                            {{-- <div class="img-circle"><img src="assets/images/user-2.svg" alt="user">
                            </div>
                            <div>
                                <h4>John Doe <span class="badge badge-themeNew">#34523</span></h4>
                                <p>Assistant Front Desk Manager</p>
                            </div> --}}
                        </div>
                    </div>
                    <div class="col-xxl-4 col-xl-5 col-md-6">
                        <label for="select_build" class="form-label">SELECT
                            BUILDINGS</label>
                        <select class="form-select select2t-none" id="select_build">
                            <option ></option>

                                @if($AvailableAccommodationModel->isNotEmpty())
                                    @foreach ($AvailableAccommodationModel as $a)
                                        <option value="{{  $a->BuildingName }}">{{ $a->BName }}</option>
                                    @endforeach

                                @endif
                        </select>
                    </div>
                    <div class="col-12"> <label class="form-label mb-2">AVAILABLE ACCOMODATION</label>
                        <div class="card card-small bg">
                            <div class="table-responsive">
                                <table class="table table-accommodation" id="table-accommodation">
                                    <thead>
                                        <tr>
                                            <th>Floor No.</th>
                                            <th>Room No.</th>
                                            <th>Employee category</th>
                                            <th>Room Facilities</th>
                                            <th>Room Status </th>
                                            <th>Bed Capacity</th>
                                            <th>Bed Availability</th>
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
                <div class="card-footer text-end">
                    <!-- <a href="#" class="btn btn-themeBlue btn-sm">Submit</a> -->
                </div>
            </div>
        </div>

    </div>
</div>

<div class="modal fade" id="selectBed-modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-small modal-selectBed">
        <div class="modal-content">
        <form id="AssignBedForm">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title" id="staticBackdropLabel">Select Bed</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="bg-themeGrayLight">
                    <div class="bed-badge">Bed</div>
                    <div class="row g-3 AppnedBed">

                    </div>
                    <input type="hidden" name="assignId" id="assignId">
                </div>
            </div>
            <div class="modal-footer">
                <!-- <a href="#" data-bs-dismiss="modal" class="btn btn-themeGray ms-auto">Cancel</a> -->
                <button href="javascript:void(0)" data-bs-toggle="modal" data-bs-dismiss="modal"
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
$(document).ready(function()
{
    $("#EmployeeList").select2({
        placeholder:'Select Employee',
        allowClear: true
    });
    $("#select_build").select2({
        placeholder:'Select Building',
        allowClear: true
    });
    $('#AssignBedForm').validate({
        rules: {
            assignId: {
                required: true,
            }
        },
        messages: {
            assignId: {
                required: "Please Select Bed.",
            }
        },
        submitHandler: function(form) {
            var formData = new FormData(form);

            formData.append("emp_id", $("#EmployeeList").val());

            $.ajax({
                url: "{{ route('resort.accommodation.AssignAccommodationToEmp') }}", // Your route for file upload
                type: "POST",
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if(response.success == true)
                    {
                        var employee = response.data.employee;
                        var accommodation = response.data.accommodation;
                        var facilities = accommodation.facilities.join(", ");
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
                            $("#reviewDetails-modal").modal('show')
                    } else {

                        toastr.error(response.message, "Error", {
                            positionClass: "toast-bottom-right",
                        });
                    }

                },
                error: function(xhr, status, error) 
                {
                    try {
                        const response = xhr.responseJSON;
                        
                        if (response && response.success === false) {
                            toastr.error(response.message, "Error", {
                                positionClass: 'toast-bottom-right'
                            });
                        } else if (response && response.errors) {
                            const errorMessages = Object.values(response.errors).flat().join('<br>');
                            toastr.error(errorMessages, "Error", {
                                positionClass: 'toast-bottom-right'
                            });
                        } else {
                            toastr.error("An unexpected error occurred", "Error", {
                                positionClass: 'toast-bottom-right'
                            });
                        }
                    } catch (e) {
                        toastr.error("An unexpected error occurred", "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                }
            });
        }
    });
});

    $(document).on("click","#Bedshow",function(){

        var id  = $(this).data('id')


                $.ajax({
                    url  : "{{ route('resort.accommodation.GetAssignedBed') }}", // Your route for file upload
                    type : "post",
                    data : {"_token":"{{ csrf_token() }}","id":id},

                    success: function(response) {
                        if (response.success) {


                            var row = ''; // Initialize row as an empty string
                                $.each(response.data, function (i, v) {

                                    if(v.emp_id!=0)
                                    {
                                        row+=` <div class="col-6">
                                            <div class="bed-block disable" >
                                                <div class="position-relative">
                                                    <img src="{{ URL::asset('resorts_assets/images/bed-active.png') }}" alt="bed">
                                                    <img src="{{ URL::asset('resorts_assets/images/check-circle-green.svg') }}" class="icon" alt="icon">
                                                    <div class="img-circle" data-bs-toggle="tooltip" data-bs-placement="bottom" title="${v.EmployeeName}"><img src="${v.profileImg}" alt="user">
                                                    </div>
                                                </div>
                                                <p>Assigned </p>
                                            </div>
                                        </div>`;
                                    }
                                    else{
                                        row += `
                                        <div class="col-6">
                                            <div class="bed-block" data-id='${v.assingid}'>
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
                                    }
                                });
                                $(".AppnedBed").html(row);
                                $('[data-bs-toggle="tooltip"]').tooltip();



                        } else {
                            toastr.error(response.message, "Error", {
                                positionClass: 'toast-bottom-right'
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
        $("#selectBed-modal").modal('show');


    });
    $(document).on("click", ".bed-block", function () {
        // Remove the 'active' class from all bed blocks
        $(".bed-block").removeClass("active");
        $(this).addClass("active");
        $("#assignId").val($(this).data('id'));
    });
    $(document).on("change","#EmployeeList",function(){

    var name = $(this).val();

    var selectedOption = $(this).find(':selected');
    var position = selectedOption.data('position');
    var pic = selectedOption.data('pic');
    var EmpId = selectedOption.data('emp_id');
    $(".empDetails-user").html(`<div class="img-circle"><img src="${pic}" alt="user">
                            </div>
                            <div>
                                <h4>${name}<span class="badge badge-themeNew">${EmpId}</span></h4>
                                <p>${position}</p>
                            </div>`);


});


    $(document).on("change","#select_build,#EmployeeList",function(){
        getAccommodationList();
    });


    function getAccommodationList()
    {
        if ($.fn.dataTable.isDataTable('#table-accommodation')) {
            // If initialized, destroy the existing instance
            $('#table-accommodation').DataTable().clear().destroy();
        }

        var InvenotryIndex = $('#table-accommodation').DataTable({
            "searching": false,
            "bLengthChange": false,
            "bFilter": true,
            "bInfo": true,
            "bAutoWidth": false,
            "scrollX": true,
            "iDisplayLength": 6,
            processing: true,
            serverSide: true,
             order: [[9, 'desc']],
            ajax: {
                    url: "{{ route('resort.accommodation.BuildingwiseAccommodation') }}",
                    type: 'GET',
                    data: function(d) {
                        d.select_build = $("#select_build").val();
                        d.Employeeid = $("#EmployeeList").val();
                    }
                },
            columns: [
                { data: 'FloorNo', name: 'FloorNo', className: 'text-nowrap' },
                { data: 'RoomNo', name: 'RoomNo', className: 'text-nowrap' },
                { data: 'EmployeeCategory', name: 'EmployeeCategory', className: 'text-nowrap' },
                { data: 'RoomFacilities', name: 'RoomFacilities', className: 'text-nowrap' },
                { data: 'RoomStatus', name: 'RoomStatus', className: 'text-nowrap' },
                { data: 'BedCapacity', name: 'BedCapacity', className: 'text-nowrap' },
                { data: 'BedAvailability', name: 'BedAvailability   ', className: 'text-nowrap' },
                { data: 'Status', name: 'Status   ', className: 'text-nowrap' },
                { data: 'Action', name: 'Action', className: 'text-nowrap' },
                 {data:'created_at',visible:false,searchable:false},

            ]
        });
    }
</script>
@endsection

