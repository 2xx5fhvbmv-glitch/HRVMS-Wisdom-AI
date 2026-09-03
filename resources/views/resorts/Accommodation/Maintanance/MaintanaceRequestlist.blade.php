
@extends('resorts.layouts.app')
@section('page_tab_title', $page_title)

@if ($message = Session::get('success'))
    <div class="alert alert-success">
        <p>{{ $message }}</p>
    </div>
@endif

@section('content')
<style>
    #maintenance-request-list-hero { padding-bottom: 40px; }
    @media (max-width: 575.98px) {
        #maintenance-request-list-hero { padding-bottom: 0; }
    }
</style>
<div class="body-wrapper pb-5">
    <div class="container-fluid">
        <div class="page-hedding" id="maintenance-request-list-hero">
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
                    <div class="col-xl-2 col-md-3 col-sm-4 col-6">
                        <select class="form-select dd-native-select" id="ResortDepartment">
                            <option></option>
                            @if($ResortDepartment->isNotEmpty())
                                @foreach ($ResortDepartment as $d)
                                    <option value="{{ $d->id}}"> {{ $d->name}}</option>
                                @endforeach
                            @endif
                        </select>
                        <div class="dd" data-target="#ResortDepartment">
                            <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                <span class="dd-lbl">Select Department</span>
                                <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                            </button>
                            <div class="dd-panel" role="listbox" aria-label="Department">
                                <div class="dd-search"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg><input type="text" placeholder="Find a department…"></div>
                                <div class="dd-scroll">
                                    <div class="dd-item active" role="option" data-value=""><span class="dd-nm">Select Department</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                    @if($ResortDepartment->isNotEmpty())
                                        @foreach ($ResortDepartment as $d)
                                        <div class="dd-item" role="option" data-value="{{ $d->id }}"><span class="dd-nm">{{ $d->name }}</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        @endforeach
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-auto ms-auto">
                        <div class="d-flex align-items-center">
                            <label for="flexSwitchCheckDefault" class="form-label mb-0 me-3">SHOW RESOLVED
                                TICKETS</label>
                            <div class="form-check form-switch form-switchTheme">
                                <input class="form-check-input SwitchResolvedTicket" type="checkbox" role="switch"
                                    id="flexSwitchCheckDefault" >
                                <label class="form-check-label" for=""></label>
                            </div>
                        </div>
                    </div>
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
                    <a href="javascript:void(0)" data-bs-dismiss="modal" class="btn eb-btn-neutral ms-auto">Cancel</a>
                    <button type='submit' class="btn eb-btn-primary">Submit</button>
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
                    <a href="javascript:void(0)" data-bs-dismiss="modal" class="btn eb-btn-neutral ms-auto">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@include('resorts._emotional_buttons_v2_styles')
@endsection

@section('import-css')
@include('resorts._dropdown_styles')
@endsection

@section('import-scripts')
<script>
    $(document).ready(function () {
        $("#select_emp").select2({
            placeholder: "Select Employee",
            allowClear: true
        });

        // ForwardToHOD now uses SweetAlert auto-approve flow (see below)
    });

    PendingTaskList()

    $(document).on("click",".ForwardToHOD",function()
    {
        var task_id = $(this).attr("data-req_id");
        var EffectedAmenity = $(this).attr("data-EffectedAmenity");
        var Location = $(this).attr("data-Location");
        var description = $(this).attr('data-description') || '';

        @php
            $engineeringHod = $Employee->first();
        @endphp
        var engineeringHodId = '{{ $engineeringHod->id ?? '' }}';

        if (!engineeringHodId) {
            toastr.error('Engineering department does not exist. Please add Engineering/Maintenance department employees first.', 'Error', { positionClass: 'toast-bottom-right' });
            return;
        }

        wisdomConfirm({
            role: 'confirm',
            title: 'Approve & Forward?',
            confirmText: 'Approve & Forward',
            extra: {
                html: `<div class="text-start">
                            <p><strong>Description:</strong> ${description}</p>
                            <p><strong>Item:</strong> ${EffectedAmenity}</p>
                            <p><strong>Location:</strong> ${Location}</p>
                            <p class="text-muted mt-2">This request will be approved and forwarded to the Engineering department.</p>
                        </div>`
            }
        }).then(function(result) {
            if (result.isConfirmed) {
                $.ajax({
                    url: "{{ route('resort.accommodation.HrForwardToHODManitenanceRequest') }}",
                    type: "POST",
                    data: {
                        _token: '{{ csrf_token() }}',
                        task_id: task_id,
                        HOD_id: engineeringHodId
                    },
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.message, 'Success', { positionClass: 'toast-bottom-right' });
                            PendingTaskList();
                        } else {
                            toastr.error(response.message || 'Failed to forward', 'Error', { positionClass: 'toast-bottom-right' });
                        }
                    },
                    error: function() {
                        toastr.error('Failed to forward request', 'Error', { positionClass: 'toast-bottom-right' });
                    }
                });
            }
        });
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
                input: 'textarea',
                inputPlaceholder: 'Enter your reason here (max 100 characters)...',
                inputValidator: (value) => {
                    if (!value) {
                        return 'Reason is required!';
                    }

                    // Check for script tags (case insensitive)
                    if (/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi.test(value)) {
                        return 'Script tags are not allowed!';
                    }

                    // Check character limit
                    if (value.length > 100) {
                        return 'Reason must be 100 characters or less!';
                    }
                },
                // Add character counter
                didOpen: () => {
                    const input = Swal.getInput();
                    const charCounter = document.createElement('div');
                    charCounter.id = 'char-counter';
                    charCounter.style.cssText = 'text-align: right; margin-top: 5px; font-size: 12px; color: #666;';
                    charCounter.innerHTML = '0/100 characters';

                    input.parentNode.appendChild(charCounter);

                    input.addEventListener('input', function() {
                        const currentLength = this.value.length;
                        charCounter.innerHTML = `${currentLength}/100 characters`;

                        // Change color based on character count
                        if (currentLength > 100) {
                            charCounter.style.color = '#d33';
                        } else if (currentLength > 80) {
                            charCounter.style.color = '#f39c12';
                        } else {
                            charCounter.style.color = '#666';
                        }
                    });
                }
            }
        }).then((result) => {
                if (result.isConfirmed) {
                    let reason = result.value.trim(); // Get the reason and trim whitespace
                    
                    // Additional client-side validation before sending
                    if (reason.length > 100) {
                        wisdomAlert({
                            type: 'error',
                            title: 'Error!',
                            text: 'Reason must be 100 characters or less!'
                        });
                        return;
                    }
                    
                    // Proceed with AJAX request
                    $.ajax({
                        url: "{{ route('resort.accommodation.MainRequestOnHold') }}",
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
                    url: '{{ route("resort.accommodation.MaintanaceRequestlist") }}',
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
                if (data.EscalationTimeOver === 'Alert' && (data.NewStatus === 'pending' || data.NewStatus === 'Pending'))
                {
                    $(row).addClass('danger-tr');
                    $(row).attr('data-bs-toggle', 'tooltip');
                    $(row).attr('data-bs-placement', 'top');
                    $(row).attr('title', '{{ $EscalationDay }} day(s) have passed for this request, kindly resolve this.');
                    new bootstrap.Tooltip(row);
                }
            }
            });

    }



</script>
@include('resorts._dropdown_script')
@endsection
