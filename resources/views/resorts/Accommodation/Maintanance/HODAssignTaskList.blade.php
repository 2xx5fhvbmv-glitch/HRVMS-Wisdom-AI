
@extends('resorts.layouts.app')
@section('page_tab_title',$page_title)

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
                     <div class="col-xl-2 col-md-3 col-sm-4 col-6">
                        <select class="form-select" id="inventory">
                            <option></option>
                            @if($inventory->isNotEmpty())
                                @foreach ($inventory as $d)
                                    <option value="{{ $d->id}}"> {{ $d->CategoryName}}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                </div>
            </div>
            <table id="" class="table OnHoldReq  table-holdReq w-100">
                <!-- <table id="" class="table   w-100"> -->
                <thead>
                    <tr>
                        <th>Description of Issue</th>
                        <th>Location </th>
                        <th>Date</th>
                        <th>Assigned Days</th>
                        <th>Assign To</th>
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

@endsection

@section('import-css')
@endsection

@section('import-scripts')
<script>

    $(document).ready(function()
    {
        $("#select_emp").select2({
            placeholder: "Select Employee",
            allowClear: true,
        });
        $("#inventory").select2({
            placeholder: "Select Inventory",
            allowClear: true,
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
        PendingTaskList();
    });
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

    $(document).on("keyup",".Search",function()
    {

        PendingTaskList();
    });
    $(document).on("change","#inventory",function()
    {
        PendingTaskList();
    });


    function PendingTaskList()
    {

        if ($.fn.dataTable.isDataTable('.OnHoldReq'))
        {
            $('.OnHoldReq').DataTable().destroy();
        }

        var TableAccomMainten = $('.OnHoldReq').DataTable({
                "searching": false,
                "bLengthChange": false,
                "bFilter": true,
                "bInfo": true,
                "bAutoWidth": false,
                "scrollX": true,
                "iDisplayLength": 6,
                processing: true,
                serverSide: true,
                order:[[7, 'desc']],
                ajax: {
                    url: '{{ route("resort.accommodation.HODAssignTaskList") }}',
                    type: 'GET',
                    data: function (d)
                    {
                        d.Search =$(".Search").val();

                        d.inventory =$("#inventory").val();
                    }
                },
                columns: [
                    { data: 'descriptionIssues', name: 'descriptionIssues', className: 'text-nowrap' },
                    { data: 'Location', name: 'Location', className: 'text-nowrap' },
                    { data: 'Date', name: 'Date', className: 'text-nowrap' },
                    { data: 'AssignedDays', name: 'AssignedDays', className: 'text-nowrap' },
                    { data: 'AssignTo', name: 'AssignTo ', className: 'text-nowrap' },
                    { data: 'Status', name: 'Status', orderable: false, searchable: false },
                    { data: 'action', name: 'action', orderable: false, searchable: false },
                     {data:'created_at',visible:false,searchable:false},


                ]
            });

    }



</script>
@endsection
