@extends('resorts.layouts.app')
@section('page_tab_title', 'Dashboard')

@if ($message = Session::get('success'))
    <div class="alert alert-success">
        <p>{{ $message }}</p>
    </div>
@endif

@section('content')
<style>
    #visa-xpact-employee-hero { padding-bottom: 40px; }
    @media (max-width: 575.98px) {
        #visa-xpact-employee-hero { padding-bottom: 0; }
    }
</style>
<div class="body-wrapper pb-5">
    <div class="container-fluid">
        <div class="page-hedding" id="visa-xpact-employee-hero">
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
                            <input type="text" id="datepickerXpact" class="form-control"
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
                        <select class="form-select dd-native-select" name="departmentFilter" id="departmentFilter">
                            <option value="all" selected>All Department</option>
                            @if($departments->isNotEmpty())
                                @foreach($departments as $department)
                                    <option value="{{ $department->id }}">{{ $department->name }}</option>
                                @endforeach
                            @endif
                        </select>
                        <div class="dd" data-target="#departmentFilter">
                            <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                <span class="dd-lbl">All Department</span>
                                <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                            </button>
                            <div class="dd-panel" role="listbox" aria-label="Department">
                                <div class="dd-search"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg><input type="text" placeholder="Find a department…"></div>
                                <div class="dd-scroll">
                                    <div class="dd-item active" role="option" data-value="all"><span class="dd-nm">All Department</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                    @if($departments->isNotEmpty())
                                        @foreach($departments as $department)
                                            <div class="dd-item" role="option" data-value="{{ $department->id }}"><span class="dd-nm">{{ $department->name }}</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        @endforeach
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-auto col-md-4 col-sm-4 col-6">
                        <select class="form-select dd-native-select" id="statusFilter">
                            <option value="All">All</option>
                            <option value="Active">Active</option>
                            <option value="InActive">InActive</option>
                        </select>
                        <div class="dd" data-target="#statusFilter">
                            <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                <span class="dd-lbl">All</span>
                                <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                            </button>
                            <div class="dd-panel" role="listbox" aria-label="Status">
                                <div class="dd-scroll">
                                    <div class="dd-item active" role="option" data-value="All"><span class="dd-nm">All</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                    <div class="dd-item" role="option" data-value="Active"><span class="dd-nm">Active</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                    <div class="dd-item" role="option" data-value="InActive"><span class="dd-nm">InActive</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                </div>
                            </div>
                        </div>
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
@include('resorts._dropdown_styles')
@include('resorts._dropdown_script')
@endsection

@section('import-css')
@endsection

@section('import-scripts')
<script>

$(document).ready(function() 
{
        FetchIndexDate();

        flatpickr('.datepickerXpact', {
            dateFormat: 'd-m-Y',
            allowInput: true,
            appendTo: document.body
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
