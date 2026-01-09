@extends('resorts.layouts.app')
@section('page_tab_title' ,"Permission Denied")


@if ($message = Session::get('success'))
<div class="alert alert-success">
	<p>{{ $message }}</p>
</div>
@endif

@section('content')<div class="body-wrapper pb-5">
    <div class="container-fluid">
        <div class="page-hedding">
            <div class="row justify-content-between g-3">
                <div class="col-auto">
                    <div class="page-title">
                        <span>WORKFORCE PLANNING</span>
                        <h1>Dashboard</h1>
                    </div>
                </div>
                <div class="col-auto">
                    <div class="d-flex justify-content-end">
                    </div>
                </div>
            </div>
        </div>
        <div class="row g-4">
            <div class="card">
                <div class="card-title">
                    <h3>Access Denied </h3>
                </div>

                <div class="card-body">
                    <h4>This page Permission is not found. Please Contact to Resort Admin</h4>
                </div>

            </div>
                
         
        </div>
    </div>
</div>
@php
$msg = config('settings.manning_request');

@endphp

<div class="modal fade" id="add-occupancymodal" tabindex="-1" aria-labelledby="exampleModalLabel"
    aria-hidden="true">
<div class="modal-dialog modal-dialog-centered modal-small">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title" id="staticBackdropLabel">Add Occupancy</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form id="AddoccupancyForm">
            @csrf
            <div class="modal-body">

                    <div class="form-group mb-20 position-relative">
                        <input type="text" class="form-control  occupancydate" name="occupancydate" placeholder="Select Date">
                    </div>

                    <div class="form-group  mb-20">
                        <input type="number" class="form-control"  min="1" name="occupancyOccupiedRooms"  id="occupancyOccupiedRooms"  placeholder="Occupied Rooms">
                    </div>
                    <div class="form-group mb-20">
                        <input type="number" class="form-control" min="1" name="occupancytotalRooms" id="occupancytotalRooms"  placeholder="Total Rooms">
                    </div>

                    <div class="form-group mb-20">
                        <input type="number" readonly min="0" class="form-control occupancyinPer"  name="occupancyinPer" placeholder="Add Occupancy In %">
                    </div>

            </div>
            <div class="modal-footer justify-content-end">

                <a href="#Import-occupancymodal"  data-bs-dismiss="modal"  data-bs-toggle="modal" class="btn btn-sm bg-green">
                    Import Occupancy
                </a>
                <a href="#" class="btn btn-sm btn-themeGray" data-bs-dismiss="modal" aria-label="Close">Cancel</a>

                <button type="submit" class="btn btn-sm btn-theme">Submit</button>
            </div>
        </form>

    </div>
</div>
</div>


<div class="modal fade" id="Import-occupancymodal" tabindex="-1" aria-labelledby="exampleModalLabel"
    aria-hidden="true">
<div class="modal-dialog modal-dialog-centered  modal-xl">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title" id="staticBackdropLabel">Import Occupancy</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form id="ImportoccupancyForm" enctype="multipart/form-data">
            @csrf
            <div class="modal-body">

                <div class="row">



                    <div class="col-md-6 ">
                        <div class="uploadFile-block">
                            <div class="uploadFile-btn">
                                <a href="#" class="btn btn-themeBlue btn-sm">Upload File</a>
                                <input type="file" class="fornm-control" name="importFile" id="importFile" accept=".xls,.xlsx">
                            </div>
                            <div class="uploadFile-text" id="ImportOccupancy">PDF or Excel</div>
                        </div>

                    </div>
                    <div class="col-md-6 ">
                        <a href="{{ URL::asset('resorts_assets/demofiles/Occupancy.xls')}}" target="_blank" class="btn btn-theme btn-small Employeefile mt-2">Download</a>

                    </div>
                </div>

            </div>
            <div class="modal-footer justify-content-end">

                <a href="#" class="btn btn-sm btn-themeGray me-2 " data-bs-dismiss="modal" aria-label="Close">Cancel</a>

                <button type="submit" class="btn btn-sm btn-theme">Submit</button>
            </div>
        </form>

    </div>
</div>
</div>

{{-- code of Notifcation for HR to HOD --}}

<div class="modal fade" id="sendRequest-modal" tabindex="-1" aria-labelledby="sendRequest-modal" aria-hidden="true">
<div class="modal-dialog modal-dialog-centered modal-xl">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title" id="staticBackdropLabel">Request Manning</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form id="RequestManning">
            @csrf
                <div class="modal-body">
                    <div class="employee-name-content">
                        <div class="row g-12">

                            <div class="col-sm-12">
                                <div class="d-flex align-items-center employee-name-box">

                                    <textarea placeholder="Enter Request Manning" name="manningRequest" id="manningRequest"class="form-control" >{{ $msg['msg1'] }}</textarea>
                                </div>
                            </div>


                        </div>
                    </div>
                </div>
                <div class="modal-footer justify-content-end">
                    <a href="#" class="btn btn-sm btn-themeGray me-2 " data-bs-dismiss="modal" aria-label="Close">Cancel</a>

                    <button type="submit" class="btn btn-sm btn-theme">Submit</button>
                </div>
        </form>

    </div>
</div>
</div>
{{-- End  --}}

{{--Code for Reminder for HOD  --}}

<div class="modal fade" id="sendReminder-modal" tabindex="-1" aria-labelledby="sendReminder-modal" aria-hidden="true">
<div class="modal-dialog modal-dialog-centered modal-xl">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title" id="staticBackdropLabel">Reminder</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form id="ReminderRequestManning">
            @csrf
                <div class="modal-body">
                    <div class="employee-name-content">
                        <div class="row g-12">

                            <div class="col-sm-12">
                                <div class="d-flex align-items-center employee-name-box">

                                        <textarea placeholder="Enter Reminder Request for Manning" name="ManningReminderRequest" id="ManningReminderRequest"class="form-control" >{{ $msg['msg2'] }}</textarea>
                                </div>
                            </div>


                        </div>
                    </div>
                </div>
                <div class="modal-footer justify-content-end">
                    <a href="#" class="btn btn-sm btn-themeGray me-2 " data-bs-dismiss="modal" aria-label="Close">Cancel</a>

                    <button type="submit" class="btn btn-sm btn-theme">Submit</button>
                </div>
        </form>

    </div>
</div>
</div>
{{-- End --}}


<div class="modal fade" id="Pending-Department" tabindex="-1" aria-labelledby="exampleModalLabel"
    aria-hidden="true">
<div class="modal-dialog modal-dialog-centered modal-small">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title" id="staticBackdropLabel">Pending Departments</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

            <div class="modal-body">

                    <div class="row">
                        <table class="table">
                            <thead>
                                    <tr>
                                        <th>Sr No</th>
                                        <th>Department Name</th>
                                    </tr>
                            </thead>
                            <tbody Class="PendingDepartmentlist">

                            </tbody>

                        </table>
                    </div>
            </div>


    </div>
</div>
</div>

@endsection

@section('import-css')
@endsection

@section('import-scripts')
<script>
 
</script>
@endsection
