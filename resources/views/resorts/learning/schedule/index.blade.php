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
                            <span>Learning & Development</span>
                            <h1>{{ $page_title }}</h1>
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <form id="training-schedule">
                    <div class="card">
                        <div class="row g-md-4 g-3 mb-md-4 mb-3">
                            <div class="col-xl-8 col-lg-7 ">
                                <div class="row g-md-4 g-3">
                                    <div class="col-12">
                                        <label for="title" class="form-label">TITLE <span class="req_span">*</span></label>
                                        <select class="form-select select2t-none" id="learning_title" name="learning_title">
                                            <option value="">Select Learning Title</option>
                                            @if($programs)
                                                @foreach($programs as $program)
                                                    <option value="{{$program->id}}">{{$program->name}}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                        <div id="div-learning_title"></div>
                                        <div id="programDetailsCard" class="d-none mt-3">
                                            <div class="row g-md-4 g-3">
                                                <div class="col-sm-6">
                                                    <label for="pdTrainer" class="form-label">TRAINER</label>
                                                    <input type="text" id="pdTrainer" class="form-control" readonly>
                                                </div>
                                                <div class="col-sm-6">
                                                    <label for="pdTrainerDept" class="form-label">TRAINER DEPARTMENT</label>
                                                    <input type="text" id="pdTrainerDept" class="form-control" readonly>
                                                </div>
                                                <div class="col-sm-6">
                                                    <label for="pdCategory" class="form-label">CATEGORY</label>
                                                    <input type="text" id="pdCategory" class="form-control" readonly>
                                                </div>
                                                <div class="col-sm-6">
                                                    <label for="pdMode" class="form-label">DELIVERY MODE</label>
                                                    <input type="text" id="pdMode" class="form-control" readonly>
                                                </div>
                                                <div class="col-sm-3">
                                                    <label for="pdHours" class="form-label">HOURS</label>
                                                    <input type="text" id="pdHours" class="form-control" readonly>
                                                </div>
                                                <div class="col-sm-3">
                                                    <label for="pdDays" class="form-label">DAYS</label>
                                                    <input type="text" id="pdDays" class="form-control" readonly>
                                                </div>
                                                <div class="col-sm-3">
                                                    <label for="pdFrequency" class="form-label">FREQUENCY</label>
                                                    <input type="text" id="pdFrequency" class="form-control" readonly>
                                                </div>
                                                <div class="col-sm-3">
                                                    <label for="pdAudienceType" class="form-label">AUDIENCE TYPE</label>
                                                    <input type="text" id="pdAudienceType" class="form-control" readonly>
                                                </div>
                                                <div class="col-12">
                                                    <label for="pdAudience" class="form-label">AUDIENCE</label>
                                                    <textarea id="pdAudience" class="form-control" rows="2" readonly></textarea>
                                                </div>
                                                <div class="col-12 d-none" id="pdObjectivesWrap">
                                                    <label for="pdObjectives" class="form-label">OBJECTIVES</label>
                                                    <textarea id="pdObjectives" class="form-control" rows="3" readonly></textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <label for="date" class="form-label">Start Date <span class="req_span">*</span></label>
                                        <input type="text" id="start_date" name="start_date" class="form-control datepicker" placeholder="Select Start Date">
                                    </div>
                                    <div class="col-sm-6">
                                        <label for="date" class="form-label">End Date <span class="req_span">*</span></label>
                                        <input type="text" id="end_date" name="end_date" class="form-control datepicker" placeholder="Select End Date">
                                    </div>
                                    <div class="col-sm-6">
                                        <label for="start_time" class="form-label">START TIME <span class="req_span">*</span></label>
                                        <input type="text" id="start_time" name="start_time" class="form-control time-field"
                                            placeholder="Select Time">
                                    </div>
                                    <div class="col-sm-6">
                                        <label for="end_time" class="form-label">END TIME <span class="req_span">*</span></label>
                                        <input type="text" id="end_time" name="end_time" class="form-control time-field"
                                            placeholder="Select Time">
                                    </div>
                                     <div class="col-12">
                                        <label for="venue" class="form-label">Venue</label>
                                        <input type="text" id="venue" name="venue" class="form-control" placeholder="Venue">
                                    </div>
                                    <div class="col-12">
                                        <label for="description" class="form-label">DESCRIPTION</label>
                                        <textarea id="description" name="description" class="form-control" rows="5" placeholder="Type Here..."></textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-4 col-lg-5">
                                <div class="bg-themeGrayLight participants-block">
                                    <div class="card-title mb-md-3">
                                        <h3>Participants</h3>
                                    </div>
                                    <input type="search" class="form-control" id="searchInput" placeholder="Search" />
                                    <div class="my-2">    
                                        <select id="departmentFilter" class="form-select select2t-none">
                                            <option value="">All Departments</option>
                                            @foreach($departments as $department)
                                                <option value="{{ $department->id }}">{{ $department->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="overflow-auto pe-1" id="employeeList">
                                        @if($employees)
                                            @foreach($employees as $employee)
                                            <div class="d-flex employee-item">
                                                <div class="img-circle userImg-block ">
                                                    <img src="{{Common::getResortUserPicture($employee->Admin_Parent_id)}}" alt="user">
                                                </div>
                                                <div class="employee-info">
                                                    <h6 class="employee-name">{{$employee->resortAdmin->full_name}}</h6>
                                                    <p class="employee-position">{{$employee->position->position_title}}</p>
                                                </div>
                                                <div class="form-check no-label">
                                                    <input class="form-check-input" type="checkbox" value="{{$employee->id}}">
                                                </div>
                                            </div>
                                            @endforeach
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card-footer text-end">
                            <button type="submit" class="btn lnd-btn-primary btn-sm">Schedule Learning</button>
                        </div>
                    </div>
                </form>
            </div>

        </div>
    </div>
@include('resorts.learning._learning_buttons_v2_styles')
@endsection

@section('import-css')
@endsection

@section('import-scripts')
<script>
    $(document).ready(function () {
        // Same themed flatpickr popover used app-wide — dateFormat matches the
        // old bootstrap-datepicker's 'dd-mm-yyyy' output exactly, so the
        // submitted string and backend parsing/validation are unchanged.
        flatpickr(".datepicker", {
            dateFormat: 'd-m-Y',
            allowInput: true,
            minDate: 'today' // Only allow today and future dates
        });

        // altInput shows 12h AM/PM (matching the old native <input type="time">
        // display) while the real, submitted field (dateFormat) stays 24h "H:i" —
        // exactly what training_schedules.start_time/end_time (a TIME column)
        // already receives raw in the controller, so the backend needs no changes.
        flatpickr(".time-field", {
            enableTime: true,
            noCalendar: true,
            dateFormat: 'H:i',
            altInput: true,
            altFormat: 'h:i K',
            time_24hr: false,
            minuteIncrement: 1,
            allowInput: true,
            // developer.min.css force-hides .flatpickr-am-pm below 424px (a mobile
            // tweak for the Duty Roster time pickers elsewhere in the app) — undo
            // it just for these instances so the AM/PM toggle stays visible here.
            onReady: function (selectedDates, dateStr, instance) {
                instance.amPM.style.setProperty('display', 'inline-block', 'important');
            }
        });

        $('.select2t-none').select2();

        // Pre-fill from query string (?program_id=X&employee_id=Y) so dashboard
        // "Schedule" buttons land on this page with the program selected and the
        // target employee's checkbox ticked.
        (function preselectFromQuery() {
            var params = new URLSearchParams(window.location.search);
            var programId = params.get('program_id');
            var employeeId = params.get('employee_id');

            if (programId) {
                $('#learning_title').val(programId).trigger('change');
            }
            if (employeeId) {
                // Wait for the employee list (some renders are async); a short delay
                // also lets Select2 settle first.
                setTimeout(function () {
                    var $cb = $('.employee-item input[type="checkbox"][value="' + employeeId + '"]');
                    if ($cb.length) {
                        $cb.prop('checked', true);
                        var $row = $cb.closest('.employee-item');
                        if ($row.length && $row[0].scrollIntoView) {
                            $row[0].scrollIntoView({block: 'center', behavior: 'smooth'});
                        }
                    }
                }, 200);
            }
        })();


        $.validator.addMethod("greaterThan", function (value, element, param) {
            let startDate = $(param).val();
            if (!startDate) return true; // Allow empty start date to avoid premature errors

            let startParts = startDate.split("-"); // Convert dd-mm-yyyy to yyyy-mm-dd
            let endParts = value.split("-");

            let startFormatted = new Date(startParts[2], startParts[1] - 1, startParts[0]);
            let endFormatted = new Date(endParts[2], endParts[1] - 1, endParts[0]);

            return endFormatted >= startFormatted; // Ensure end date is greater or equal to start date
        }, "End date must be greater than or equal to the start date.");

        $.validator.addMethod("greaterThanTime", function (value, element, param) {
            let startTime = $(param).val();
            if (!startTime || !value) return true; // Allow empty values to avoid premature errors

            // Convert time strings (HH:MM) to Date objects
            let startParts = startTime.split(":");
            let endParts = value.split(":");

            let startFormatted = new Date(2000, 1, 1, startParts[0], startParts[1]); // Dummy date
            let endFormatted = new Date(2000, 1, 1, endParts[0], endParts[1]); // Dummy date

            return endFormatted > startFormatted; // Ensure end time is strictly greater than start time
        }, "End time must be greater than start time.");

        $.validator.addMethod("customDate", function(value, element) {
            // Check if the value matches dd-mm-yyyy format using regex
            return this.optional(element) || /^\d{2}-\d{2}-\d{4}$/.test(value);
        }, "Please enter a valid date in dd-mm-yyyy format.");

        $("#training-schedule").validate({
            rules: {
                learning_title: {
                    required: true,
                },
                start_date: {
                    required: true,
                    customDate: true,
                },
                end_date: {
                    required: true,
                    customDate: true, // Use the custom date validation
                    greaterThan: "#start_date", // Custom rule applied here
                },
                start_time: {
                    required: true,
                },
                end_time: {
                    required: true,
                    greaterThanTime: "#start_time", // Ensure end time > start time
                },
                venue: {
                    required: true,
                }
            },
            messages: {
                learning_title: {
                    required: "Please select the learning title.",
                },
                training_date: {
                    required: "Please select a training date.",
                },
                start_time: {
                    required: "Please select a start time.",
                },
                end_time: {
                    required: "Please select an end time greater than start time.",
                },
                venue: {
                    required: "Please enter a venue.",
                }
            },
            submitHandler: function (form) {
                var formData = new FormData(form);

                let selectedEmployees = [];
                $(".employee-item input[type='checkbox']:checked").each(function () {
                    selectedEmployees.push($(this).val());
                });

                if (selectedEmployees.length === 0) {
                    toastr.error("Please select at least one employee.", "Error",{
                        positionClass: 'toast-bottom-right',
                    });
                    return false;
                }

                formData.append("employee_ids", JSON.stringify(selectedEmployees));

                $.ajax({
                    url: "{{ route('learning.schedule.store') }}",
                    type: "POST",
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function (response) {
                        if (response.success) {
                            toastr.success(response.msg, "Success",{
                                positionClass: 'toast-bottom-right',
                            });
                            form.reset();
                            // form.reset() only clears the underlying <input> elements.
                            // Each date/time field's flatpickr instance keeps its own
                            // selected-date state and (for time) a separate altInput
                            // display, neither of which reset() touches — clear() does.
                            $(form).find('.datepicker, .time-field').each(function () {
                                if (this._flatpickr) {
                                    this._flatpickr.clear();
                                }
                            });
                        } else {
                            toastr.error(response.msg, "Error",{
                                positionClass: 'toast-bottom-right',
                            });
                        }
                    },
                    error: function (response) {
                        var errors = response.responseJSON.errors;
                        var errorMessages = "";
                        $.each(errors, function (key, error) {
                            errorMessages += error + "<br>";
                        });
                        toastr.error(errorMessages, "Error",{
                            positionClass: 'toast-bottom-right',
                        });
                    },
                });
            },
            // jQuery Validate ignores :hidden fields by default. The time flatpickr's
            // altInput turns #start_time/#end_time into type="hidden" (the visible
            // control is a sibling altInput), which would otherwise silently drop
            // their required/greaterThanTime rules.
            ignore: ':hidden:not(#start_time, #end_time)',
            errorPlacement: function(error, element) {
                if (element.attr("name") == "learning_title") {
                    error.insertAfter("#div-learning_title");
                } else {
                    error.insertAfter(element);
                }
            },
            errorElement: 'span'
        });

        // ── Auto-fill description / trainer / duration / audience when a program is picked ──
        // Also auto-suggests end_date / end_time using the program's days+hours
        // (only when start_date / start_time are already entered). The user can
        // still edit any prefilled value manually.
        $('#learning_title').on('change', function () {
            var programId = $(this).val();
            if (!programId) {
                $('#description').val('');
                $('#programDetailsCard').addClass('d-none');
                return;
            }

            $.ajax({
                url: '{{ url("/resort/learning/program") }}/' + programId + '/detail',
                type: 'GET',
                success: function (response) {
                    if (!response.success) {
                        $('#programDetailsCard').addClass('d-none');
                        return;
                    }
                    var d = response.data;

                    // Pre-fill the editable Description textarea.
                    $('#description').val(d.description || '');

                    // Populate the read-only form fields with program metadata.
                    $('#pdTrainer').val(d.trainer_name
                        ? d.trainer_name + (d.trainer_position ? ' (' + d.trainer_position + ')' : '')
                        : '');
                    $('#pdTrainerDept').val(d.trainer_department || '');
                    $('#pdCategory').val(d.category || '');
                    $('#pdMode').val(d.delivery_mode || '');
                    $('#pdHours').val(d.hours ? d.hours + ' hr' : '');
                    $('#pdDays').val(d.days || '');
                    $('#pdFrequency').val(d.frequency || '');
                    $('#pdAudienceType').val(d.audience_type || '');
                    $('#pdAudience').val(
                        (d.audience_labels && d.audience_labels.length)
                            ? d.audience_labels.join(', ')
                            : ''
                    );
                    if (d.objectives) {
                        $('#pdObjectives').val(d.objectives);
                        $('#pdObjectivesWrap').removeClass('d-none');
                    } else {
                        $('#pdObjectives').val('');
                        $('#pdObjectivesWrap').addClass('d-none');
                    }
                    $('#programDetailsCard').removeClass('d-none');

                    // Suggest end_date from start_date + days (only if end_date is empty).
                    var startDateStr = $('#start_date').val();
                    if (startDateStr && d.days && !$('#end_date').val()) {
                        var p = startDateStr.split('-');
                        if (p.length === 3) {
                            var sd = new Date(p[2], parseInt(p[1], 10) - 1, parseInt(p[0], 10));
                            sd.setDate(sd.getDate() + (parseInt(d.days, 10) - 1));
                            var pad = function (n) { return n < 10 ? '0' + n : '' + n; };
                            $('#end_date').val(pad(sd.getDate()) + '-' + pad(sd.getMonth() + 1) + '-' + sd.getFullYear());
                        }
                    }

                    // Suggest end_time from start_time + hours (only if end_time is empty).
                    var startTimeStr = $('#start_time').val();
                    if (startTimeStr && d.hours && !$('#end_time').val()) {
                        var t = startTimeStr.split(':');
                        if (t.length >= 2) {
                            var startMins = parseInt(t[0], 10) * 60 + parseInt(t[1], 10);
                            var endMins = startMins + Math.round(parseFloat(d.hours) * 60);
                            endMins = Math.max(0, Math.min(endMins, 24 * 60 - 1));
                            var hh = Math.floor(endMins / 60);
                            var mm = endMins % 60;
                            var endTimeStr = (hh < 10 ? '0' + hh : hh) + ':' + (mm < 10 ? '0' + mm : mm);
                            // Plain .val() only touches the hidden real input, not the
                            // altInput the user sees — go through the flatpickr instance
                            // so the visible 12h field updates too.
                            var endTimeFp = document.getElementById('end_time')._flatpickr;
                            if (endTimeFp) {
                                endTimeFp.setDate(endTimeStr, true, 'H:i');
                            } else {
                                $('#end_time').val(endTimeStr);
                            }
                        }
                    }
                },
                error: function (xhr) {
                    $('#programDetailsCard').addClass('d-none');
                    console.error('Failed to load program detail', xhr && xhr.status, xhr && xhr.responseText);
                }
            });
        });

        $('#departmentFilter').on('change', function () {
            let deptID = $(this).val();

            $.ajax({
                url: "{{ route('get.employees.deptwise') }}",
                type: "POST",
                data: {
                    _token: "{{ csrf_token() }}",  // CSRF Token required for Laravel
                    deptID: deptID
                },
                success: function (response) {
                    if (response.success) {
                        let employeeList = $('#employeeList');
                        employeeList.empty(); // Clear old data

                        // Append new employee data
                        $.each(response.employees, function (index, employee) {
                            let employeeHtml = `
                                <div class="d-flex employee-item">
                                    <div class="img-circle userImg-block">
                                        <img src="${employee.image}" alt="user">
                                    </div>
                                    <div class="employee-info">
                                        <h6 class="employee-name">${employee.full_name}</h6>
                                        <p class="employee-position">${employee.position_title}</p>
                                    </div>
                                    <div class="form-check no-label">
                                        <input class="form-check-input" type="checkbox" value="${employee.id}">
                                    </div>
                                </div>
                            `;
                            employeeList.append(employeeHtml);
                        });
                    } else {
                        toastr.error(response.msg, "Error",{
                            positionClass: 'toast-bottom-right',
                        });
                    }
                },
                error: function (response) {
                    let errors = response.responseJSON.errors;
                    let errorMessages = "";
                    $.each(errors, function (key, error) {
                        errorMessages += error + "<br>";
                    });
                    toastr.error(errorMessages, "Error",{
                        positionClass: 'toast-bottom-right',
                    });
                }
            });
        });
    });
    document.addEventListener("DOMContentLoaded", function () {
        let searchInput = document.getElementById("searchInput");
        let employeeItems = document.querySelectorAll(".employee-item");

        searchInput.addEventListener("input", function () {
            let filter = searchInput.value.toLowerCase().trim();

            employeeItems.forEach(function (employee) {
                let name = employee.querySelector(".employee-name").textContent.toLowerCase();
                let position = employee.querySelector(".employee-position").textContent.toLowerCase();

                if (name.includes(filter) || position.includes(filter)) {
                    employee.classList.add("d-flex");  // Show matching items
                    employee.classList.remove("d-none"); 
                } else {
                    employee.classList.remove("d-flex");  
                    employee.classList.add("d-none");  // Hide non-matching items
                }
            });
        });
    });


</script>
@endsection