
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

        <div class="card">
            <div class="card-header">
                <div class="row g-md-3 g-2 align-items-center">
                    <div class="col-xl-3 col-lg-5 col-md-7 col-sm-8 ">
                        <div class="input-group">
                            <input type="search" class="form-control Search" placeholder="Search" />
                            <i class="fa-solid fa-search"></i>
                        </div>
                    </div>
                    {{-- <div class="col-xl-2 col-md-5 col-sm-4 col-6">
                        <select class="form-select">
                            <option selected>Maintenance Types</option>
                            <option value="1">abc</option>
                            <option value="2">abc</option>
                        </select>
                    </div> --}}
                    {{-- <div class="col-xl-2 col-md-3 col-sm-4 col-6">
                        <select class="form-select" id="ResortDepartment">
                            <option></option>
                            @if($ResortDepartment->isNotEmpty())
                                @foreach ($ResortDepartment as $d)
                                    <option value="{{ $d->id}}"> {{ $d->name}}</option>
                                @endforeach
                            @endif
                        </select>
                    </div> --}}

                    {{-- <div class="col-auto ms-auto">
                        <div class="d-flex align-items-center">
                            <label for="flexSwitchCheckDefault" class="form-label mb-0 me-3">SHOW RESOLVED
                                TICKETS</label>
                            <div class="form-check form-switch form-switchTheme">
                                <input class="form-check-input SwitchResolvedTicket" type="checkbox" role="switch"
                                    id="flexSwitchCheckDefault" >
                                <label class="form-check-label" for=""></label>
                            </div>
                        </div>
                    </div> --}}
                </div>
            </div>
            <!-- data-Table  -->
            <table id="" class="table table-accomMainten  w-100">
                <thead>
                    <tr>
                        <th>Requested By</th>
                        <th>Affected Amenity </th>
                        <th>Location </th>
                        <th>Priority</th>
                        <th>Assigned Staff</th>
                        <th>Date</th>
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
<div class="modal fade" id="ForwardToHOD-Model" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-small modal-assign">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="staticBackdropLabel">Assign Task To HOD</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="ForwardToHODForm">
                @csrf
                <div class="modal-body">
                    <div class="bg-themeGrayLight DetailsShow">

                    </div>
                    <div><label for="select_emp" class="form-label">SELECT EMPLOYEE</label>
                        <select class="form-select select2t-none" name="HOD_id" id="select_emp" aria-label="Default select example">
                            <option> </option>
                            @if($Employee->isNotEmpty())
                                @foreach ($Employee as $e)
                                    <option value="{{ $e->id}}"> {{ $e->resortAdmin->first_name}} {{ $e->resortAdmin->last_name}}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                    <input type="hidden" name="task_id" id="task_id">
                </div>
                <div class="modal-footer">
                    <a href="javascript:void(0)" data-bs-dismiss="modal" class="btn btn-themeGray ms-auto">Cancel</a>
                    <button type='submit' class="btn btn-themeBlue">Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>
<div class="modal fade" id="ForwardToHOD-DetailsModel" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-small modal-assign">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="staticBackdropLabel">Task Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

                <div class="modal-body">
                    <div class="row">
                        <table class="table ShowInternalDetails">

                        </table>
                    </div>
                    <input type="hidden" name="task_id" id="task_id">
                </div>
                <div class="modal-footer">
                    <a href="javascript:void(0)" data-bs-dismiss="modal" class="btn btn-themeGray ms-auto">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('import-css')
@endsection

@section('import-scripts')
<script>
    $(document).ready(function () {
        $("#select_emp").select2({
            placeholder: "Select Employee",
            allowClear: true
        });
        $("#ResortDepartment").select2({
            placeholder: "Select Department",
            allowClear: true
        });

        $('#ForwardToHODForm').validate({
            rules: {
                HOD_id: {
                    required: true,
                }
            },
            messages: {
                HOD_id: {
                    required: "Please Select HOD.",
                }
            },
            submitHandler: function(form) {
                var formData = new FormData(form);

                $.ajax({
                    url: "{{ route('resort.accommodation.HrForwardToHODManitenanceRequest') }}", // Your route for file upload
                    type: "POST",
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.message, "Success", {
                                positionClass: 'toast-bottom-right'
                            });
                            form.reset();
                            PendingTaskList();
                            $("#ForwardToHOD-Model").modal('hide');

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
            }
        });
    });

    PendingTaskList()

    $(document).on("click",".ForwardToHOD",function()
    {

        var EffectedAmenity = $(this).attr("data-EffectedAmenity");
        var Location = $(this).attr("data-Location");

        var task_id = $(this).attr("data-req_id");

        var  row =    `<table>
                        <tr>
                            <th>Item:</th>
                            <td>${EffectedAmenity}</td>
                        </tr>
                        <tr>
                            <th>Location:</th>
                            <td>${Location}</td>
                        </tr>
                    </table>`;
       $(".DetailsShow").html(row);
       $("#task_id").val(task_id);
        $("#ForwardToHOD-Model").modal('show');
    });



    $(document).on("click", ".OnHoldRequest", function() {
        let task_id = $(this).data('task_id');
        let flag = $(this).data('flag');
        let msg = (flag === "On-Hold") ? 'Yes, put it on hold!' : 'Yes, close it!';

        // SweetAlert confirmation dialog with input field
        Swal.fire({
            title: 'Are you sure?',
            text: msg,
            icon: 'warning',
            input: 'textarea', // Input type for providing a reason
            inputPlaceholder: 'Enter your reason here...',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: msg,
            inputValidator: (value) => {
                if (!value) {
                    return 'Reason is required!';
                }
            }
        }).then((result) => {
            if (result.isConfirmed) {
                let reason = result.value; // Get the reason entered by the user

                // Proceed with AJAX request
                $.ajax({
                    url: "{{ route('resort.accommodation.MainRequestOnHold') }}", // Your route for the request
                    type: "GET",
                    data: {
                        "task_id": task_id,
                        "flag": flag,
                        "reason": reason,
                        "_token": "{{ csrf_token() }}"
                    },
                    success: function(response) {
                        if (response.success) {
                            // Show success SweetAlert
                            Swal.fire({
                                title: 'Success!',
                                text: response.message,
                                icon: 'success',
                                confirmButtonColor: '#3085d6'
                            });
                            PendingTaskList(); // Refresh task list
                        } else {
                            Swal.fire({
                                title: 'Error!',
                                text: response.message || "Something went wrong.",
                                icon: 'error',
                                confirmButtonColor: '#d33'
                            });
                        }
                    },
                    error: function(response) {
                        let errors = response.responseJSON;
                        let errs = errors?.errors ? Object.values(errors.errors).join('<br>') : "An unexpected error occurred.";

                        // Show error SweetAlert
                        Swal.fire({
                            title: 'Error!',
                            html: errs,
                            icon: 'error',
                            confirmButtonColor: '#d33'
                        });
                    }
                });
            }
        });
    });

    $(document).on("change",".SwitchResolvedTicket ,#ResortDepartment",function()
    {
            PendingTaskList();
    });
    $(document).on("keyup",".Search",function()
    {
            PendingTaskList();
    });

    function PendingTaskList()
    {

        if ($.fn.dataTable.isDataTable('.table-accomMainten'))
        {
            $('.table-accomMainten').DataTable().destroy();
        }

        var TableAccomMainten = $('.table-accomMainten').DataTable({
                "searching": false,
                "bLengthChange": false,
                "bFilter": true,
                "bInfo": true,
                "bAutoWidth": false,
                "scrollX": true,
                "iDisplayLength": 6,
                processing: true,
                serverSide: true,
                order:[[8, 'desc']],
                ajax: {
                    url: '{{ route("resort.accommodation.HODMaintanaceRequestlist") }}',
                    type: 'GET',
                    data: function (d)
                    {
                        d.flag = $(".SwitchResolvedTicket").prop('checked');
                        d.Search =$(".Search").val();
                        d.ResortDepartment = $("#ResortDepartment").val();
                    }
                },
                columns: [
                    { data: 'RequestedBy', name: 'RequestedBy', className: 'text-nowrap' },
                    { data: 'EffectedAmenity', name: 'EffectedAmenity', className: 'text-nowrap' },
                    { data: 'Location', name: 'Location', className: 'text-nowrap' },
                    { data: 'Priority', name: 'Priority', className: 'text-nowrap' },
                    { data: 'AssgingedStaff', name: 'AssgingedStaff ', className: 'text-nowrap' },
                    { data: 'Date', name: 'Date', className: 'text-nowrap' },
                    { data: 'Status', name: 'Status', className: 'text-nowrap' },
                    { data: 'action', name: 'action', orderable: false, searchable: false },
                     {data:'created_at',visible:false,searchable:false},
                ],
                rowCallback: function (row, data, index) {
                if (data.EscalationTimeOver === 'Alert'  && data.NewStatus===  'pending' || data.NewStatus===  'In-Progress' || data.NewStatus===  'Assigned')
                {
                    $(row).addClass('danger-tr');
                }
            }
            });

    }



</script>
@endsection
