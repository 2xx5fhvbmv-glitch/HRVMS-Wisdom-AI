@extends('resorts.layouts.app')
@section('page_tab_title' ,$page_title)

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
                        <span>Performance</span>
                        <h1>{{ $page_title }}</h1>
                    </div>
                </div>
                <div class="col-auto">
                    <div class="d-flex justify-content-end">
                        <a href="{{ route('Performance.Meeting.list') }}" class="btn perf-btn-secondary">
                            <i class="fa-solid fa-list me-1"></i> View All Meetings
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div>
            <div class="card">
                <form id="ScheduleMeetingForm">
                    <div class="row g-md-4 g-3 mb-md-4 mb-3">
                        <div class="col-xl-8 col-lg-7 ">
                            <div class="row g-md-4 g-3">
                                <div class="col-12">
                                    <label for="title" class="form-label">TITLE <span class="red-mark"><span class="red-mark">*</span></span></label>
                                    <input type="text" id="title" name="title" class="form-control" placeholder="Subject">
                                </div>
                                <div class="col-sm-4">
                                    <label for="date" class="form-label">Date<span class="red-mark">*</span></label>
                                    <input type="text" id="date" name="date" class="form-control datepicker" placeholder="Select Date">
                                </div>
                                <div class="col-sm-4">
                                    <label for="start_time" class="form-label">Start Time<span class="red-mark">*</span></label>
                                    <input type="time" id="start_time" name="start_time" class="form-control timepicker" placeholder="Select Time">
                                </div>
                                <div class="col-sm-4">
                                    <label for="end_time" class="form-label">End Time<span class="red-mark">*</span></label>
                                    <input type="time" id="end_time" name="end_time" class="form-control timepicker" placeholder="Select Time">
                                </div>
                                <div class="col-12">
                                    <p class="text-muted mb-1" style="font-size:12px;">Please fill in either Location or Meeting Link (at least one is required)</p>
                                </div>
                                <div class="col-sm-6">
                                    <label for="location" class="form-label">Location</label>
                                    <input type="text" id="location" name="location" class="form-control" placeholder="Enter a location for the in-person meeting">
                                </div>
                                <div class="col-sm-6">
                                    <label for="conference_link" class="form-label">Meeting Link</label>
                                    <input type="text" id="conference_link" name="conference_link" class="form-control" placeholder="Enter a link for the remote meeting">
                                </div>
                                <div class="col-12">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea id="description"  name="description" class="form-control" rows="5" placeholder="Type Here..."></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-4 col-lg-5">
                            <div class="bg-themeGrayLight participants-block">
                                <div class="card-title mb-md-3">
                                    <h3>Participants <span class="red-mark">*</span></h3>
                                </div>
                            {{-- Blade Template --}}
                                <div class="search-container mb-3">
                                    <input type="text" class="form-control" id="searchEmployee" placeholder="Search employees...">
                                </div>

                                <div class="overflow-auto pe-1" id="employeeList">
                                    @if($employees->isNotEmpty())
                                        <div class="employee-container">
                                            @foreach ($employees as $e)
                                            <div class="d-flex employee-item"> {{-- Parent element for each employee --}}
                                                <div class="img-circle userImg-block">
                                                    <img src="{{ $e->profileImg }}" alt="user">
                                                </div>
                                                <div class="employee-details">
                                                    <h6 class="employee-name">{{ $e->EmployeeName }}</h6>
                                                    <p class="position-name">{{ $e->positionName }}</p>
                                                </div>
                                                <div class="form-check no-label">
                                                    <input class="form-check-input" type="checkbox" name="Emp_id[]" value="{{ $e->Emp_id }}">
                                                </div>
                                            </div>
                                            @endforeach
                                        </div>
                                        <div class="no-results alert alert-info" style="display: none;">
                                            No matching records found
                                        </div>
                                    @else
                                        <div class="alert alert-info">No employees found</div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer text-end"><button type="submit" class="btn perf-btn-primary btn-sm ScheduleMeeting @if(App\Helpers\Common::checkRouteWisePermission('Performance.Meeting.index',config('settings.resort_permissions.create')) == false) d-none @endif">Schedule Meeting</button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>
@include('resorts.Performance._performance_buttons_v2_styles')
@endsection

@section('import-css')
@endsection

@section('import-scripts')
<script>
    $(document).ready(function () {

    flatpickr('#date', {
        dateFormat: 'm/d/Y',
        allowInput: true,
        appendTo: document.body
    });
    const $employeeContainer = $('.employee-container');
    const $noResults = $('.no-results');

    // Track selected employees across searches
    window.selectedEmployees = window.selectedEmployees || {};

    // Persist selection state when checkbox changes
    $(document).on('change', "input[name='Emp_id[]']", function() {
        var val = $(this).val();
        if ($(this).is(':checked')) {
            window.selectedEmployees[val] = true;
        } else {
            delete window.selectedEmployees[val];
        }
    });

    // On initial load, capture any pre-checked employees
    $("input[name='Emp_id[]']:checked").each(function() {
        window.selectedEmployees[$(this).val()] = true;
    });

    $("#searchEmployee").on("keyup", function ()
    {
        let searchValue = $(this).val().toLowerCase().trim();

        $.ajax({
            url: "{{ route('Performance.Meeting.GetPerformanceEmp') }}",
            type: "POST",
            data: {
                "_token":"{{ csrf_token() }}",
                "searchValue":searchValue
            },
            success: function (response) {
                if (response.success) {
                    let employeeList = $("#employeeList");
                    employeeList.empty();
                    if (response.data === 0)
                    {
                        employeeList.append("<p>No results found.</p>");
                    }
                    else
                    {
                        response.data.forEach((e) => {
                            var checked = window.selectedEmployees[e.Emp_id] ? 'checked' : '';
                            let employeeHtml = `<div class="employee-item">
                                                    <div class="d-flex">
                                                        <div class="img-circle userImg-block">
                                                            <img src="${e.profileImg}" alt="user">
                                                        </div>
                                                        <div>
                                                            <h6 class="employee-name">${e.EmployeeName}</h6>
                                                            <p class="position-name">${e.positionName}</p>
                                                        </div>
                                                        <div class="form-check no-label">
                                                            <input class="form-check-input" type="checkbox" name="Emp_id[]" value="${e.Emp_id}" ${checked}>
                                                        </div>
                                                    </div>
                                                </div>`;
                            employeeList.append(employeeHtml);
                        });
                    }
                } else {
                    toastr.error(response.message, "Error", {
                        positionClass: "toast-bottom-right",
                    });
                }
            },
            error: function (response)
            {
                var errors = response.responseJSON;
                var errs = '';
                $.each(errors.errors, function (key, error) {
                    errs += error + '<br>';
                });
                toastr.error(errs, "Error", {
                    positionClass: "toast-bottom-right",
                });
            }
        });

    });
    $('#ScheduleMeetingForm').validate({
        rules: {
            title: {
                required: true,
            },
            date: {
                required: true,
            },
            start_time: {
                required: true,
            },
            end_time: {
                required: true,
                // greaterThanStart: true // Custom validation rule
            },
            location: {
                required: function() {
                    return $('#conference_link').val().trim() === '';
                },
            },
            conference_link: {
                required: function() {
                    return $('#location').val().trim() === '';
                },
            },
            description: {
                required: true,
            },
            "Emp_id[]": {
                required: true,
            }
        },
        messages: {
            title: {
                required: "Please Enter Meeting Title",
            },
            date: {
                required: "Please Enter Meeting Date",
            },
            start_time: {
                required: "Please Enter Meeting Start Time",
            },
            end_time: {
                required: "Please Enter Meeting End Time",
                // greaterThanStart: "End time must be greater than start time" // Custom message
            },
            location: {
                required: "Please Enter Location or Meeting Link",
            },
            conference_link: {
                required: "Please Enter Meeting Link or Location",
            },
            description: {
                required: "Please Enter Meeting Description",
            },
            "Emp_id[]": {
                required: "Please Select Employee",
            }
        },
        submitHandler: function (form, event) {
            event.preventDefault();

            var selectedIds = Object.keys(window.selectedEmployees || {});
            if (selectedIds.length === 0) {
                toastr.error('Please select at least one employee', 'Error', { positionClass: 'toast-bottom-right' });
                return false;
            }

            var formData = new FormData(form);
            formData.delete('Emp_id[]');
            selectedIds.forEach(function(empId) {
                formData.append('Emp_id[]', empId);
            });

            $(".ScheduleMeeting").attr('disabled',true);
            $.ajax({
                url: "{{ route('Performance.Meeting.ScheduleMeetingEmp') }}",
                type: "POST",
                data: formData,
                processData: false,
                contentType: false,
                success: function (response) {
                    if (response.success) {
                        toastr.success(response.message, "success", {
                            positionClass: "toast-bottom-right",
                        });

                        // Reset form and selections
                        $('#ScheduleMeetingForm')[0].reset();
                        $(".PerformanceMeetingSelectEmp").html('');
                        window.selectedEmployees = {};
                        $("input[name='Emp_id[]']").prop('checked', false);
                        $(".ScheduleMeeting").attr('disabled',false);
                    }
                    else
                    {
                        toastr.error(response.message, "Error", {
                            positionClass: "toast-bottom-right",
                        });
                    }
                },
                error: function (response)
                {
                    if(response.status === 422) 
                 {
                        toastr.error("Validation error occurred.", "Error", {
                            positionClass: "toast-bottom-right",
                        });
                    } 
                    else
                    {
                        toastr.error("An error occurred while scheduling the meeting.", "Error", {
                            positionClass: "toast-bottom-right",
                        });
                    }
                    $(".ScheduleMeeting").attr('disabled',false);
                    var errors = response.responseJSON;
                    var errs = '';
                    $.each(errors.errors, function (key, error) {
                        errs += error + '<br>';
                    });
                    toastr.error(errs, "Error", {
                        positionClass: "toast-bottom-right",
                    });
                }
            });
        }
    });

// Custom validation method for start_time < end_time
$.validator.addMethod("greaterThanStart", function(value, element) {
    var startTime = $('#start_time').val();
    var endTime = $('#end_time').val();

    if (startTime && endTime) {
        return new Date("1970/01/01 " + endTime) > new Date("1970/01/01 " + startTime); // Compare time
    }
    return true;
}, "End time must be greater than start time.");

});

// Optional: Add this if you need to clear the search
function clearSearch() {
    $('#searchEmployee').val('');
    $('.employee-item').show();
    $('.no-results').hide();
}


    $(document).on("click",".ScheduleMeeting",function(){

        let Emp_id = $("input[name='Emp_id[]']:checked").map(function() {
                return $(this).val();
            }).get();
        console.log(Emp_id);
    });

</script>
@endsection
