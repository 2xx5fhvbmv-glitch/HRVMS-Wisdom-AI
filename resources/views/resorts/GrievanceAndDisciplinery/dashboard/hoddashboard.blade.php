@extends('resorts.layouts.app')
@section('page_tab_title' ," People Relation Dashboard")

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
                        <span>People Relation</span>
                        <h1>Dashboard</h1>
                    </div>
                </div>
                {{-- <div class="col-auto"> <a href="#" class="btn btn-theme">Export</a></div> --}}
            </div>
        </div>

        <div class="row g-3 g-xxl-4 card-heigth">
            <div class="col-xl-9 col-12">
                <div class="card">
                    <div class=" card-title">
                        <div class="row justify-content-between align-items-center g-md-3 g-1">
                            <div class="col">
                                <h3 class="text-nowrap">Maintenance Request</h3>
                            </div>
                            {{-- <div class="col-auto">
                                <div class="form-group">
                                    <select class="form-select ResortDepartment" aria-label="Default select example">
                                        <option ></option>
                                        @foreach ($ResortDepartment as $d)
                                            <option value="{{ $d->id }}">{{ $d->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div> --}}
                            <div class="col-auto"><a target="_blank" href="{{ route('resort.accommodation.HODMaintanaceRequestlist') }}" class="a-link">View All</a>
                            </div>
                        </div>
                    </div>
                    <table id="" class="table  table-accomMainten  w-100">
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
            <div class="col-xl-3 col-md-6">
                <div class="card card-accomSummary">
                    <div class="card-title">
                        <h3>Summary</h3>
                    </div>
                    <div class="leaveUser-main">
                        <div class="leaveUser-bgBlock">
                            <h6>Total number of open requests</h6>
                            <h3>{{ $Totalnumberofopenrequests }}</h3>
                        </div>
                        <div class="leaveUser-bgBlock">
                            <h6>Number of high-priority requests</h6>
                            <h3>{{ $TotalnumberofHighrequests }}</h3>
                        </div>
                        <div class="leaveUser-bgBlock">
                            <h6>Number of requests nearing completion.</h6>
                            <h3>{{ $TotalnumberofInProgressrequests }}</h3>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-6 col-md-6">
                <div class="card card-wiINsight card-accomWiINsight">
                    <div class="card-title">
                        <div class="row g-md-2 g-1 align-items-center">
                            <div class="col">
                                <h3>WI Insight's</h3>
                            </div>
                            <div class="col-auto">
                                <a href="#" class="a-link">View All</a>
                            </div>
                        </div>
                    </div>
                    <div class="leaveUser-main">
                        <div class="leaveUser-block">
                            <div class="img">
                                <img src="assets/images/wisdom-ai-small.svg" alt="image">
                            </div>
                            <div>
                                <h6>Wisdom suggested This room for John Doe</h6>
                                <P>Lorem ipsum is simply dummy text of the typesetting industry Lorem typesetting
                                    industry ipsum. Lorem ipsum is simply dummy text of the typesetting industry
                                    Lorem typesetting industry ipsum.</P>
                                <div>
                                    <a href="#" class="a-linkTheme">View Details</a>
                                    <a href="#" class="a-link">Request Leave</a>
                                </div>
                            </div>
                        </div>
                        <div class="leaveUser-block">
                            <div class="img">
                                <img src="assets/images/wisdom-ai-small.svg" alt="image">
                            </div>
                            <div>
                                <h6>Upcoming Vacancies:</h6>
                                <P>Prediction of the forthcoming vacancies based on staff leaves, contract
                                    terminations, or transfers.</P>
                                <div>
                                    <a href="#" class="a-linkTheme">View Details</a>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
            <div class="col-xl-6 col-12">
                <div class="card">
                    <div class=" card-title">
                        <div class="row  g-md-2 g-1  align-items-center">
                            <div class="col">
                                <h3 class="text-nowrap">Assigned Tasks</h3>
                            </div>
                            <div class="col-auto"><a href="{{ route('resort.accommodation.HODAssignTaskList') }}" class="a-link">View All</a>
                            </div>
                        </div>
                    </div>
                    <!-- data-Table -->
                    <table id="" class="table  table-assignTask w-100">
                        <thead>
                            <tr>
                                <th>Description of Issue</th>
                                <th>Location</th>
                                <th>Date </th>
                                <th>Asssign To</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>

                        </tbody>
                    </table>

                </div>
            </div>
            <div class="col-xl-12">
                <div class="card">
                    <div class=" card-title">
                        <div class="row justify-content-between align-items-center g-md-3 g-1">
                            <div class="col">
                                <h3 class="text-nowrap">On Hold Requested </h3>
                            </div>
                            <div class="col-auto">
                                <a target="_blank" href="{{ route("resort.accommodation.HODHoldMaintanaceRequest") }}" class="a-link">Viwe All</a>
                            </div>
                        </div>
                    </div>
                    <!-- data-Table  -->
                    <table id="" class="table OnHoldReq table-holdReqAccom w-100">
                        <thead>
                            <tr>
                                <th>Description of Issue</th>
                                <th>Location </th>
                                <th>Date</th>
                                <th>Priority</th>
                                <th>Reason For On-Hold</th>
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
                        <select class="form-select select2t-none" name="emp_id" id="select_emp" aria-label="Default select example">
                            <option> </option>
                            @if($Employee->isNotEmpty())

                                @foreach ($Employee as $e)
                                    <option value="{{ $e->id}}"> {{ $e->first_name}} {{ $e->last_name}}</option>
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
@endsection

@section('import-css')
@endsection

@section('import-scripts')
<script type="text/javascript">
    $(document).ready(function () {
        $(".ResortDepartment").select2({
            placeholder: "Select Department",
            allowClear: true
        });
        $("#select_emp").select2({
            placeholder: "Select Employee",
            allowClear: true
        });

        $('#ForwardToHODForm').validate({
            rules: {
                emp_id: {
                    required: true,
                }
            },
            messages: {
                emp_id: {
                    required: "Please Select HOD.",
                }
            },
            submitHandler: function(form) {
                var formData = new FormData(form);

                $.ajax({
                    url: "{{ route('resort.accommodation.HodAssignToEmp') }}", // Your route for file upload
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
        tableassignTask();
        OnHoldTaskList();
    });
    $(".ResortDepartment").on("change",function()
        {
            PendingTaskList();
        });
    // tooltip
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    })

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


    function PendingTaskList()
    {
        if ($.fn.dataTable.isDataTable('.table-accomMainten')) {
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
                url: '{{ route("resort.accommodation.HODGetMaintananceRequest") }}',
                type: 'GET',
                data: function (d) {
                    d.ResortDepartment = $(".ResortDepartment").val();
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
            ]
        });
    }

    function tableassignTask()
    {
        if ($.fn.dataTable.isDataTable('.table-assignTask')) {
            $('.table-assignTask').DataTable().destroy();
        }

        var TableAccomMainten = $('.table-assignTask').DataTable({
            "searching": false,
            "bLengthChange": false,
            "bFilter": true,
            "bInfo": true,
            "bAutoWidth": false,
            "scrollX": true,
            "iDisplayLength": 6,
            processing: true,
            serverSide: true,
            order:[[5, 'desc']],
            ajax: {
                url: '{{ route("resort.accommodation.HODtableassignTask") }}',
                type: 'GET',
                data: function (d) {
                    d.ResortDepartment = $(".ResortDepartment").val();
                }
            },
            columns: [
                { data: 'DescriptionOfIssue', name: 'DescriptionOfIssue', className: 'text-nowrap' },
                { data: 'Location', name: 'Location', className: 'text-nowrap' },
                { data: 'Date', name: 'Date', className: 'text-nowrap' },
                { data: 'AssgingedStaff', name: 'AssgingedStaff ', className: 'text-nowrap' },
                { data: 'action', name: 'action', orderable: false, searchable: false },
                {data:'created_at',visible:false,searchable:false},
            ]
        });
    }



    function OnHoldTaskList()
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
                order:[[6, 'desc']],
                ajax: {
                    url: '{{ route("resort.accommodation.HODHoldMaintanaceRequest") }}',
                    type: 'GET',
                    data: function (d)
                    {
                        d.Search =$(".Search").val();
                    }
                },
                columns: [
                    { data: 'descriptionIssues', name: 'descriptionIssues', className: 'text-nowrap' },
                    { data: 'Location', name: 'Location', className: 'text-nowrap' },
                    { data: 'Date', name: 'Date', className: 'text-nowrap' },

                    { data: 'Priority', name: 'Priority', className: 'text-nowrap' },
                    { data: 'ReasonOnHold', name: 'ReasonOnHold ', className: 'text-nowrap' },
                    { data: 'action', name: 'action', orderable: false, searchable: false },
                    {data:'created_at',visible:false,searchable:false},
                ]
            });
    }
</script>

@endsection
