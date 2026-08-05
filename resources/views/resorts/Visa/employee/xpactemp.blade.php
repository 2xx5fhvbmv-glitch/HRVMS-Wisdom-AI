@extends('resorts.layouts.app')
@section('page_tab_title', 'Dashboard')

@if ($message = Session::get('success'))
    <div class="alert alert-success">
        <p>{{ $message }}</p>
    </div>
@endif

@section('content')
<div class="body-wrapper pb-5">
    <div class="container-fluid">
        <div class="page-hedding">
            <div class="row  g-3">
                <div class="col-auto">
                    <div class="page-title">
                        <span>Visa Management</span>
                        <h1>Xpat Employees</h1>
                    </div>
                </div>
                <!-- <div class="col-auto ms-auto"><a class="btn btn-theme">btn</a></div> -->
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
                    {{-- <div class="col-xl-2 col-md-4 col-sm-4 col-6">
                        <div class="input-group">
                            <input type="text" id="datepickerXpact" class="form-control datepickerXpact"
                                placeholder="01/08/2024">

                        </div>
                    </div> --}}
                    <!-- <div class="col-xl-auto col-md-4 col-sm-4 col-6">
                        <select class="form-select">
                            <option selected>By Joining date</option>
                            <option value="1">abc</option>
                            <option value="2">abc</option>
                        </select>

                    </div> -->
                    <div class="col-xl-2 col-md-4 col-sm-4 col-6">
                        <select class="form-select" name="departmentFilter" id="departmentFilter">
                            {{-- Non-empty value "all" so Select2 renders it as a real
                                 selectable row (an empty value is consumed as the
                                 placeholder slot and never appears in the list). --}}
                            <option value="all" selected>All Department</option>
                            @if($departments->isNotEmpty())
                                @foreach($departments as $department)
                                    <option value="{{ $department->id }}">{{ $department->name }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                    <div class="col-xl-auto col-md-4 col-sm-4 col-6">
                        <select class="form-select" id="statusFilter">
                            <option value="All">All</option>
                            <option value="Active">Active</option>
                            <option value="InActive">InActive</option>
                        </select>
                    </div>

                </div>
            </div>
            <!-- data-Table  -->
            <table id="Table-XpactEmp" class="table data-Table  Table-XpactEmp w-100">
                <thead>
                    <tr>
                        <th>Profile</th>
                        <th>Employee ID</th>
                        <th>Employee Name</th>
                        <th>Nationality</th>
                        <th>Position</th>
                        <th>Department</th>
                        <th>Joining Date</th>
                        <th>Status</th>
                        <th>Insurance</th>
                        <th>Work Permit Due</th>
                        <th>Medical Expiry</th>
                        <th>Slot Payment Due</th>
                        <th>Active</th>

                    </tr>
                </thead>
                <tbody>
                    
                </tbody>
            </table>

        </div>


    </div>
</div>
@include('resorts._emotional_buttons_v2_styles')
@endsection

@section('import-css')
@endsection

@section('import-scripts')
<script>

$(document).ready(function() 
{
        FetchIndexDate();    

        $("#statusFilter").select2({
            placeholder: "Select Status",
            allowClear: true,
            
        });
        $("#departmentFilter").select2({
            // "All Department" is now a real option (value="all"), selected by
            // default — no placeholder needed. A placeholder here would only
            // re-introduce the empty-slot behaviour.
            minimumResultsForSearch: 0,
        });
        $(".datepickerXpact").datepicker({
            format: 'dd-mm-yyyy',
            autoclose: true,
            todayHighlight: true
        });
        
    $("#statusFilter").on("change", function() {
        FetchIndexDate()
    });
     $(".Search").on("keyup", function() {
        FetchIndexDate()
    });
    $("#datepickerXpact").on("change", function() {
        FetchIndexDate()
    });
     $("#departmentFilter").on("change", function() {
        FetchIndexDate()
    });
    
});
function FetchIndexDate()
{
        if($.fn.DataTable.isDataTable('#Table-XpactEmp'))
        {
            $('#Table-XpactEmp').DataTable().destroy();
        }
        var productTable = $('#Table-XpactEmp').DataTable({
            "searching": false,  // Disable the default search box
            "bLengthChange": false, // Disable page length dropdown
            "bInfo": true,  // Show table information
            "bAutoWidth": false, // Auto width disabled
            "scrollX": true,  // Enable horizontal scrolling
            "iDisplayLength": 15,  // Set the initial number of records per page
            processing: true, // Show processing indicator
            serverSide: true, // Enable server-side processing
            order: [[13, 'desc']],  // Default order by created_at (hidden, last column)
            ajax: {
                url: "{{ route('resort.visa.xpactEmployee') }}",
                type: 'GET',
                data: function(d) {
                    d.searchTerm = $('.Search').val();
                    d.status = $("#statusFilter").val();
                    d.departmentFilter = $("#departmentFilter").val();

                    d.date = $("#datepickerXpact").val();
                }
            },
            columns: [
                    { data: 'Profile', name: 'Profile', orderable: false, searchable: false },
                    { data: 'EmployeeId', name: 'EmployeeId' },
                    { data: 'EmployeeName', name: 'EmployeeName' },
                    { data: 'Nationality', name: 'Nationality' },
                    { data: 'position', name: 'Position' },
                    { data: 'department', name: 'Department' },
                    { data: 'JoiningDate', name: 'JoiningDate' },
                    { data: 'status', name: 'status' },
                    { data: 'Insurance', name: 'Insurance' },
                    { data: 'WorkPermitDue', name: 'WorkPermitDue' },
                    { data: 'MedicalExpiry', name: 'MedicalExpiry' },
                    { data: 'SlotPaymentDue', name: 'SlotPaymentDue' },
                    { data: 'action', name: 'action' },
                    {data:'created_at', visible:false,searchable:false}
            ]
        });
         
}

</script>
@endsection
