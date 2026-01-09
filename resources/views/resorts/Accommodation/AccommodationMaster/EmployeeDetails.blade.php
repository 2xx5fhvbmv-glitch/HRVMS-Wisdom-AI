

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
            <div class="card-header mb-2">
                <div class="empDetails-user">
                    <div class="img-circle"><img src="{{ Common::getResortUserPicture($employee->resortAdmin->id) }}" alt="user">
                    </div>
                    <div>
                        <h4>{{ $employee->resortAdmin->full_name ?? "-"}}<span class="badge badge-themeNew">{{ $employee->Emp_id }}</span></h4>
                        <p>{{ $employee->position->position_title ?? "-" }}</p>
                    </div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-lableNew  table-empAccom">
                    <tbody>
                        <tr>
                            <th>Joining Date:</th>
                            <td>{{ $employee->joining_date ?? 'N/A'}}</td>
                        </tr>
                        <tr>
                            <th>Accommodation Type:</th>
                            <td>{{ $data->AccommodationName ?? "-"}}</td>
                        </tr>
                        <tr>
                            <th>Location:</th>
                            <td>{{ $data->BName ?? "-" }}</td>
                        </tr>
                        <tr>
                            <th>Floor:</th>
                            <td>{{ $data->Floor ?? "-" }}</td>
                        </tr>
                        <tr>
                            <th>Room No.</th>
                            <td>{{ $data->Room ?? "-" }}</td>
                        </tr>
                        <tr>
                            <th>Amenities Provided:</th>
                            <td>
                                @if(!empty($data->itemData))

                                @php
                                    $d = implode(",",$data->itemData);
                                    echo $d;
                                @endphp

                                @endif

                            </td>
                        </tr>
                        <tr>
                            <th>Move-in Date:</th>
                            <td>{{ $data->effected_date ?? "-"}}</td>
                        </tr>
                        <tr>
                            <th>Previous Accommodations:</th>
                            <td>@if($history->isNotEmpty())
                                    @foreach ($history as $h)
                                            {{ $h->NewDate ?? '-' }} <br>
                                    @endforeach
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Sharing With:</th>
                            <td>
                                @if($AssingAccommodation->isNotEmpty())
                                    @foreach ($AssingAccommodation as $a)
                                    <div class="tableUser-block">
                                        <div class="img-circle"><img src="{{  $a->profileImg }}" alt="user">
                                        </div>
                                        <span class="userApplicants-btn">{{  $a->EmployeeName}}</span>
                                    </div>
                                    @endforeach
                                @endif

                            </td>
                        </tr>
                        <tr>
                            <th>Cleaning Facility:</th>
                            <td>{{ $data->CleaningSchedule ?? "-" }}</td>
                        </tr>
                        <tr>
                            <th>Duration of Stay:</th>
                            <td>{{ $data->effected_date_diff ?? "-" }}</td>
                        </tr>
                        {{-- <tr>
                            <th>Rate:</th>
                            <td>
                                <div class="rating">
                                    <i class="fa-solid fa-star"></i>
                                    <i class="fa-solid fa-star"></i>
                                    <i class="fa-solid fa-star"></i>
                                    <i class="fa-solid fa-star"></i>
                                    <i class="fa-solid fa-star"></i>
                                </div>
                            </td>
                        </tr> --}}
                    </tbody>
                </table>
            </div>
            <div class="card-footer border-0 pt-2">
                <div class="row justify-content-end g-3">
                    <div class="col-auto">
                        <a href="javascript:void(0)"  data-AvailableAccommodation_ID="{{ $data->AvailableAccommodation_ID ?? '' }}" class="btn btn-themeSkyblue btn-sm MoveToNext @if(Common::checkRouteWisePermission('resort.accommodation.EmployeeAccommodation',config('settings.resort_permissions.edit')) == false) d-none @endif">Move In To Another</a>
                    </div>
                    <div class="col-auto">
                        <a href="javascript:void(0)"  data-AvailableAccommodation_ID="{{ $data->AvailableAccommodation_ID ?? '' }}" class="btn btn-themeBlue AssignAmmenity btn-sm @if(Common::checkRouteWisePermission('resort.accommodation.EmployeeAccommodation',config('settings.resort_permissions.edit')) == false) d-none @endif">Assign Ammenity</a>
                    </div>
                </div>
            </div>

        </div>


    </div>
</div>

<div class="modal fade" id="EmploydetailsAccommodtion-modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="staticBackdropLabel">Assign Ammenity </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="AddMoreItems">
                    @csrf
                    <div class="row">
                        <div class="col-md-12">
                            <input type="hidden" name="AvailableAccommodation_ID" value="{{ $data->AvailableAccommodation_ID ?? '' }}">

                            <select class="form-select select2t-none " multiple name="InventoryModule[]" >
                                @if($InventoryModule->isNotEmpty())
                                    @foreach ($InventoryModule as $a)

                                    <option value="{{ $a->id }}"
                                        @if(in_array($a->id, $data->item_id)) selected @endif>
                                        {{ $a->ItemName }}
                                    </option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                    </div>
                    <hr>

                    <div class="row g-2 justify-content-center mb-3">
                        <div class="col-auto">
                            <button type="submit" class="btn btn-sm btn-theme">Submit</button>

                            <button type="button" class="btn btn-sm btn-themeGray me-2" data-bs-dismiss="modal">Cancel</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="EmployeeMove-modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="staticBackdropLabel">Move In To Another </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="AddMoreItems">
                    @csrf
                    <div class="row">
                        <input type="hidden" name="Accommodation_ID" value="{{ $data->AvailableAccommodation_ID ?? '' }}">

                        <div class="col-md-6">
                            <label >Select Building</label>

                            <select name="Building_id" id="Building_id">
                                <option ></option>
                                @if($BuildingModel->isNotEmpty())
                                    @foreach ($BuildingModel as $b)
                                        <option value="{{ $b->building_id }}">{{ $b->BuildingName }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        <hr class="mt-2">
                        <div class="col-md-12 mt-1">
                            <label >AVAILABLE ACCOMODATION</label>
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
                    <hr>

                    {{-- <div class="row g-2 justify-content-center mb-3">
                        <div class="col-auto">
                            <button type="submit" class="btn btn-sm btn-theme">Submit</button>

                            <button type="button" class="btn btn-sm btn-themeGray me-2" data-bs-dismiss="modal">Cancel</button>
                        </div>
                    </div> --}}
                </form>
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
                    <input type="hidden" name="MoveToNext" value="Yes">
                    <input type="hidden" name="OldAssingedId" id="OldAssingedId" value="{{ $data->AvailableAccommodation_ID ?? ''}}">
                    <input type="hidden" name="ChildBedId" id="ChildBedId" value="{{ $data->ChildBedId ?? '' }}">
                    

                </div>
                <hr>
                <div class="bg-themeGrayLight">
                    <div class="row">
                        <div class="col-md-12">
                            <textarea class="form-control" placeholder="Please Enter Reason" name="Reason" id="Reason"></textarea>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
              <a href="javascript:void(0)" data-bs-dismiss="modal" class="btn btn-themeGray ms-auto">Cancel</a> 
                <button type="submit" class="btn btn-themeBlue">Submit</button>
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
                <a href="javascript:void(0)" data-bs-toggle="modal" data-bs-dismiss="modal" class="btn btn-themeBlue hideallthePopUp">Submit</a>
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
        $("#Building_id").select2({
            placeholder: "Building",
            allowClear: true
        });
        
            $('#AddMoreItems').validate({
                rules: {
                    InventoryModule: {
                        required: true,
                    }
                },
                messages: {
                    InventoryModule: {
                        required: "Please Select Invenotory Items.",
                    }
                },
                submitHandler: function(form) {
                    var formData = new FormData(form);


                    $.ajax({
                        url: "{{ route('resort.accommodation.AssignMoreAccommodationToEmp') }}", // Your route for file upload
                        type: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            if (response.success)
                            {

                                $("#EmploydetailsAccommodtion-modal").modal('hide');
                                toastr.success(response.message, "Success", {
                                    positionClass: "toast-bottom-right",
                                });
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
                }
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
                submitHandler: function(form) 
                {
                    var formData = new FormData(form);
                    formData.append("emp_id", "{{ $employee->Emp_id }}");
                    $.ajax({
                        url: "{{ route('resort.accommodation.MoveToNext') }}",
                        type: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) 
                        {
                            setTimeout(() => {
                            $("#AssignBedForm").modal('hide');
                        }, 100);
                        
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

                               
                            } 
                            else
                            {
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
    
    $(document).on('click', '.hideallthePopUp', function()
    {
        $("#EmploydetailsAccommodtion-modal").modal('hide');
        $("#EmployeeMove-modal").modal('hide');
        $("#reviewDetails-modal").modal('hide');
        $("#selectBed-modal").modal('hide');
        $("#AssignBedForm").trigger('reset');
        window.location.reload();


    });
    $(document).on('click', '.AssignAmmenity', function()
    {
        $("#EmploydetailsAccommodtion-modal").modal('show');
    });
    $(document).on('click', '.MoveToNext', function()
    {
        $("#EmployeeMove-modal").modal('show');
    });
        $(document).on('change', '#Building_id', function()
        {
            getAccommodationList();
        });
        $(document).on("click","#Bedshow",function(){
            var id  = $(this).data('id');
            $.ajax({
                url  : "{{ route('resort.accommodation.GetAssignedBed') }}", // Your route for file upload
                type : "post",
                data : {"_token":"{{ csrf_token() }}","id":id},

                success: function(response) {
                    if (response.success) 
                    {
                        var row = '';
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

                            $("#selectBed-modal").modal('show');

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
     
        });
        $(document).on("click", ".bed-block", function (){
            $(".bed-block").removeClass("active");
            $(this).addClass("active");
            $("#assignId").val($(this).data('id'));
        });
        $(document).on("change","#EmployeeList",function()
        {
            var name = $(this).val();
            var selectedOption = $(this).find(':selected');
            var position = selectedOption.data('position');
            var pic = selectedOption.data('pic');
            var EmpId = selectedOption.data('emp_id');
            $(".empDetails-user").html(`<div class="img-circle"><img src="${pic}" alt="user"></div><div><h4>${name}<span class="badge badge-themeNew">${EmpId}</span></h4><p>${position}</p></div>`);
        });
    function getAccommodationList()
    {
        if ($.fn.dataTable.isDataTable('#table-accommodation')) 
        {
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
            ajax: {
                    url: "{{ route('resort.accommodation.BuildingwiseAccommodation') }}",
                    type: 'GET',
                    data: function(d) {
                        d.select_build = $("#Building_id").val();
                        d.Employeeid = "{{ $employee->Emp_id }}";
                        d.flag="change";
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

            ]
        });
    }
</script>
@endsection
