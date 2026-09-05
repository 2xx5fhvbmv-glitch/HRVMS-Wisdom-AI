
@extends('resorts.layouts.app')
@section('page_tab_title', $page_title)

@if ($message = Session::get('success'))
    <div class="alert alert-success">
        <p>{{ $message }}</p>
    </div>
@endif

@section('content')
<style>
    #assign-accommodation-hero { padding-bottom: 40px; }
    @media (max-width: 575.98px) {
        #assign-accommodation-hero { padding-bottom: 0; }
    }
</style>
<div class="body-wrapper pb-5">
    <div class="container-fluid">
        <div class="page-hedding" id="assign-accommodation-hero">
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
                        <select class="form-select dd-native-select" id="EmployeeList">

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
                        @php $firstEmp = $Employeelist->first(); @endphp
                        <div class="dd" data-target="#EmployeeList">
                            <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                <span class="dd-lbl">{{ $firstEmp ? $firstEmp->first_name . ' ' . $firstEmp->last_name : 'Select Employee' }}</span>
                                <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                            </button>
                            <div class="dd-panel" role="listbox" aria-label="Employee">
                                <div class="dd-search"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg><input type="text" placeholder="Find an employee…"></div>
                                <div class="dd-scroll">
                                    @if($Employeelist->isNotEmpty())
                                        @foreach ($Employeelist as $e)
                                        <div class="dd-item{{ $loop->first ? ' active' : '' }}" role="option" data-value="{{ $e->new_emp_id }}"><span class="dd-nm">{{ $e->first_name }} {{ $e->last_name }}</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        @endforeach
                                    @endif
                                </div>
                            </div>
                        </div>
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
                        <select class="form-select dd-native-select" id="select_build">
                            <option ></option>

                                @if($AvailableAccommodationModel->isNotEmpty())
                                    @foreach ($AvailableAccommodationModel as $a)
                                        <option value="{{  $a->BuildingName }}">{{ $a->BName }}</option>
                                    @endforeach

                                @endif
                        </select>
                        <div class="dd" data-target="#select_build">
                            <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                <span class="dd-lbl">Select Building</span>
                                <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                            </button>
                            <div class="dd-panel" role="listbox" aria-label="Building">
                                <div class="dd-search"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg><input type="text" placeholder="Find a building…"></div>
                                <div class="dd-scroll">
                                    <div class="dd-item active" role="option" data-value=""><span class="dd-nm">Select Building</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                    @if($AvailableAccommodationModel->isNotEmpty())
                                        @foreach ($AvailableAccommodationModel as $a)
                                        <div class="dd-item" role="option" data-value="{{ $a->BuildingName }}"><span class="dd-nm">{{ $a->BName }}</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        @endforeach
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12"> <label class="form-label mb-2">AVAILABLE ACCOMMODATION</label>
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

<div class="modal fade" id="selectBed-modal" aria-labelledby="exampleModalLabel" aria-hidden="true">
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
                <!-- <a href="#" data-bs-dismiss="modal" class="btn eb-btn-neutral ms-auto">Cancel</a> -->
                <button href="javascript:void(0)" data-bs-toggle="modal" data-bs-dismiss="modal"
                    class="btn eb-btn-primary">Submit</button>
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
                <a href="javascript:void(0)" data-bs-dismiss="modal" class="btn eb-btn-neutral">Cancel</a>
                <button type="button" class="btn eb-btn-primary" id="confirmAssignBed">Confirm Assignment</button>
            </div>
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
    // Fix Bootstrap 5 + Select2 focus conflict inside modals
    // Bootstrap 5 traps focus inside modal, preventing Select2 search from working
    $(document).on('select2:open', function(e) {
        var evt = "scroll.select2";
        $(e.target).parents().off(evt);
        $(window).off(evt);
        var searchField = document.querySelector('input.select2-search__field');
        if (searchField) {
            searchField.focus();
        }
    });

    // Override Bootstrap 5 modal focus trap for Select2 compatibility
    document.addEventListener('DOMContentLoaded', function() {
        var modalEl = document.getElementById('selectBed-modal');
        if (modalEl) {
            modalEl.addEventListener('shown.bs.modal', function() {
                // Remove Bootstrap's focusin listener that blocks Select2
                $(document).off('focusin.bs.modal');
            });
        }
    });
</script>
<script>
$(document).ready(function()
{
    $('#AssignBedForm').validate({
        rules: {
            assignId: {
                required: true,
            }
        },
        messages: {
            assignId: {
                required: "Please select a bed.",
            }
        },
        submitHandler: function(form) {
            var empId = $("#EmployeeList").val();
            var assignId = $("#assignId").val();

            if (!empId) {
                toastr.error("Please select an employee first", "Error", { positionClass: 'toast-bottom-right' });
                return;
            }

            // Store for confirmation
            window._pendingAssignment = { emp_id: empId, assignId: assignId };

            // Fetch preview data from server
            $.ajax({
                url: "{{ route('resort.accommodation.previewAccommodation') }}",
                type: "POST",
                data: { _token: '{{ csrf_token() }}', emp_id: empId, assignId: assignId },
                success: function(response) {
                    if (response.success) {
                        var emp = response.data.employee;
                        var acc = response.data.accommodation;
                        var facilities = acc.facilities.join(", ") || '-';

                        $(".appendhereAfterAssign").html(`
                            <div class="empDetails-user">
                                <div class="img-circle"><img src="${emp.profile_picture}" alt="user"></div>
                                <div>
                                    <h4>${emp.name} <span class="badge badge-themeNew">${emp.emp_id}</span></h4>
                                    <p>${emp.position}</p>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-lable">
                                    <tr><th>Building:</th><td>${acc.building_name}</td></tr>
                                    <tr><th>Floor:</th><td>${acc.floor}</td></tr>
                                    <tr><th>Room No.</th><td>${acc.room_no}</td></tr>
                                    <tr><th>Bed No.</th><td>${acc.bed_no}</td></tr>
                                    <tr><th>Room Facilities:</th><td>${facilities}</td></tr>
                                    <tr><th>Room Status:</th><td>${acc.RoomStatus}</td></tr>
                                    <tr><th>Accommodation Name:</th><td>${acc.accommodation_name}</td></tr>
                                </table>
                            </div>
                        `);

                        $("#selectBed-modal").modal('hide');
                        setTimeout(function() {
                            $("#reviewDetails-modal").modal('show');
                        }, 300);
                    } else {
                        toastr.error(response.message || 'Failed to load preview', "Error", { positionClass: 'toast-bottom-right' });
                    }
                },
                error: function() {
                    toastr.error('Failed to load preview', "Error", { positionClass: 'toast-bottom-right' });
                }
            });
        }
    });
});

    // Confirm Assignment - actually assign the bed
    $(document).on("click", "#confirmAssignBed", function() {
        var pending = window._pendingAssignment;
        if (!pending) {
            toastr.error("No pending assignment", "Error", { positionClass: 'toast-bottom-right' });
            return;
        }

        var $btn = $(this);
        $btn.prop('disabled', true).text('Assigning...');

        $.ajax({
            url: "{{ route('resort.accommodation.AssignAccommodationToEmp') }}",
            type: "POST",
            data: {
                _token: '{{ csrf_token() }}',
                emp_id: pending.emp_id,
                assignId: pending.assignId
            },
            success: function(response) {
                if (response.success) {
                    $("#reviewDetails-modal").modal('hide');
                    window._pendingAssignment = null;
                    getAccommodationList();
                    toastr.success(response.message || 'Bed assigned successfully', "Success", {
                        positionClass: "toast-bottom-right",
                    });
                } else {
                    toastr.error(response.message || 'Failed to assign', "Error", {
                        positionClass: "toast-bottom-right",
                    });
                }
            },
            error: function(xhr) {
                var msg = 'An error occurred';
                if (xhr.responseJSON) {
                    msg = xhr.responseJSON.message || Object.values(xhr.responseJSON.errors || {}).flat().join(', ') || msg;
                }
                toastr.error(msg, "Error", { positionClass: 'toast-bottom-right' });
            },
            complete: function() {
                $btn.prop('disabled', false).text('Confirm Assignment');
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


                            var row = '';
                                $.each(response.data, function (i, v) {
                                    var bedLabel = v.BedNo || ('Bed ' + (i+1));

                                    if(v.emp_id!=0)
                                    {
                                        row+=` <div class="col-6">
                                            <div class="bed-block disable">
                                                <div class="position-relative">
                                                    <img src="{{ URL::asset('resorts_assets/images/bed-active.png') }}" alt="bed">
                                                    <img src="{{ URL::asset('resorts_assets/images/check-circle-green.svg') }}" class="icon" alt="icon">
                                                    <div class="img-circle" data-bs-toggle="tooltip" data-bs-placement="bottom" title="${v.EmployeeName}"><img src="${v.profileImg}" alt="user">
                                                    </div>
                                                </div>
                                                <p>${v.EmployeeName}<br><small class="text-muted">${bedLabel}</small></p>
                                            </div>
                                        </div>`;
                                    }
                                    else{
                                        row += `
                                        <div class="col-6">
                                            <div class="bed-block" data-id='${v.assingid}' data-bed-no='${bedLabel}'>
                                                <div class="position-relative">
                                                    <img src="{{ URL::asset('resorts_assets/images/bed-active.png') }}" alt="bed">
                                                    <img src="{{ URL::asset('resorts_assets/images/check-circle-green.svg') }}" class="icon" alt="icon">
                                                    <div class="img-circle" data-bs-toggle="tooltip" data-bs-placement="bottom"
                                                        title="Available"><img src="{{ URL::asset('resorts_assets/images/user-2.svg') }}" alt="user">
                                                    </div>
                                                </div>
                                                <p>Available<br><small class="text-muted">${bedLabel}</small></p>
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

    var selectedOption = $(this).find(':selected');
    var empName = selectedOption.text().trim();
    var position = selectedOption.data('position');
    var pic = selectedOption.data('pic');
    var EmpId = selectedOption.data('emp_id');
    if (empName && empName !== 'Select Employee') {
        $(".empDetails-user").html(`<div class="img-circle"><img src="${pic}" alt="user">
                                </div>
                                <div>
                                    <h4>${empName} <span class="badge badge-themeNew">${EmpId}</span></h4>
                                    <p>${position}</p>
                                </div>`);
    }


});

    // Trigger on page load if employee already selected (browser auto-restore)
    setTimeout(function() {
        if ($("#EmployeeList").val()) {
            $("#EmployeeList").trigger('change');
        }
    }, 500);

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
@include('resorts._dropdown_script')
@endsection

