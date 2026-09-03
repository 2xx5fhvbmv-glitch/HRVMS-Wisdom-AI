
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
                        <select class="form-select dd-native-select" id="inventory">
                            <option></option>
                            @if($inventory->isNotEmpty())
                                @foreach ($inventory as $d)
                                    <option value="{{ $d->id}}"> {{ $d->CategoryName}}</option>
                                @endforeach
                            @endif
                        </select>
                        <div class="dd" data-target="#inventory">
                            <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                <span class="dd-lbl">Select Category</span>
                                <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                            </button>
                            <div class="dd-panel" role="listbox" aria-label="Inventory category">
                                <div class="dd-search"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg><input type="text" placeholder="Find a category…"></div>
                                <div class="dd-scroll">
                                    <div class="dd-item active" role="option" data-value=""><span class="dd-nm">Select Category</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                    @if($inventory->isNotEmpty())
                                        @foreach ($inventory as $d)
                                        <div class="dd-item" role="option" data-value="{{ $d->id }}"><span class="dd-nm">{{ $d->CategoryName }}</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        @endforeach
                                    @endif
                                </div>
                            </div>
                        </div>
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
@include('resorts._dropdown_styles')
@endsection

@section('import-scripts')
<script>

    $(document).ready(function()
    {
        $("#select_emp").select2({
            placeholder: "Select Employee",
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
                    required: "Please select HOD.",
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
        wisdomConfirm({
            role: 'confirm',
            title: 'Are you sure?',
            text: msg,
            confirmText: msg,
            extra: {
                input: 'textarea', // Input type for providing a reason
                inputPlaceholder: 'Enter your reason here...',
                inputValidator: (value) => {
                    if (!value) {
                        return 'Reason is required!';
                    }
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
                            wisdomAlert({
                                type: 'success',
                                title: 'Success!',
                                text: response.message
                            });
                            PendingTaskList(); // Refresh task list
                        } else {
                            wisdomAlert({
                                type: 'error',
                                title: 'Error!',
                                text: response.message || "Something went wrong."
                            });
                        }
                    },
                    error: function(response) {
                        let errors = response.responseJSON;
                        let errs = errors?.errors ? Object.values(errors.errors).join('<br>') : "An unexpected error occurred.";

                        // Show error SweetAlert
                        wisdomAlert({
                            type: 'error',
                            title: 'Error!',
                            extra: { html: errs }
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
@include('resorts._dropdown_script')
@endsection
