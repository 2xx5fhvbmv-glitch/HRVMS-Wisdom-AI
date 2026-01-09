@extends('resorts.layouts.app')
@section('page_tab_title' ,"Dashboard")

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
                            <span>Time And Attendance</span>
                            <h1>Create Duty Roster</h1>
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <div class="card ">
                <form id="DutyRosterForm">
                    @csrf()
                    <div class="row g-xl-4 g-3 mb-3">

                        <div class="col-lg-4 col-md-6 createDuty-emp">
                            <div class="mb-md-4 mb-3">
                                <label for="select-emp" class="form-label">SELECT EMPLOYEE</label>
                                    <select class="form-select" name="Emp_id" id="Employee">
                                        <option></option> <!-- Leave this blank for the placeholder -->
                                        @if($employees->isNotEmpty())
                                            @foreach ($employees as $e)
                                                <option value="{{ $e->id }}">{{ ucfirst($e->first_name . ' ' . $e->last_name) }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                            </div>
                            <div class="createduty-Append">

                            </div>
                        </div>
                        <div class="col-lg-4 col-md-6 ">
                            <div class="card-themeSkyblue h-100 overflow-hidden">
                                <div id="datapicker">
                                    <!-- Hidden input field to attach the calendar to -->
                                    <input type="hidden" name="hiddenInput" id="hiddenInput">
                                </div>
                                <p id="startDate" class="d-none">Start Date:</p>
                                <p id="endDate" class="d-none">End Date:</p>
                            </div>
                        </div>
                        <div class="col-lg-4  createDuty-date">
                            <div class="card-themeSkyblue ">
                                <div class="row  g-lg-4 g-sm-3 g-2">
                                    <div class="col-lg-12 col-sm-6">
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="checkbox" id="DefaultShiftTime" value="All" name="DefaultShiftTime" checked="">
                                            <label class="form-check-label" for="DefaultShiftTime">Set default shift time</label>
                                        </div>
                                    </div>
                                    <div class="col-lg-12 col-sm-6">
                                        <input type="text" class="form-control " name="MakeShift" disabled id="MakeShift" placeholder="10 Sep - 14 Sep">


                                    </div>
                                    <div class="col-lg-12 col-sm-6">
                                        <select class="form-select select2t-none" id="Shift"
                                            aria-label="Default select example" name="Shift">
                                            <option></option> <!-- Leave this blank for the placeholder -->
                                            @if($ShiftSettings->isNotEmpty())
                                                @foreach ($ShiftSettings as $s)
                                                        <?php
                                                            $start = new DateTime('21:00');
                                                            $end = new DateTime('07:00');
                                                            if ($end < $start)
                                                            {
                                                                $end->modify('+1 day');
                                                            }
                                                            $interval = $start->diff($end);
                                                            $totalHours = $interval->h + ($interval->days * 24);
                                                            $totalMinutes = $interval->i;
                                                            $TotalHours =  $totalHours . ":" . $totalMinutes;
                                                        ?>
                                                    <option value="{{ $s->id }}"  data-totalHrs ="{{ $TotalHours }}"> {{ ucfirst($s->ShiftName) }}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <a href="javascript:void(0)" class="a-link addOvertime-modal">Add Overtime</a>
                                        <input type="text" class="form-control overtime"  name="overtime" >
                                    </div>
                                    <div class="col-lg-12 col-sm-6">
                                        <select class="form-select select2t-none DayOfDate" id="DayOfDate"
                                            aria-label="Default select example" name="DayOfDate">
                                            <option selected value=""></option> <!-- Leave this blank for the placeholder -->
                                            @if(!empty($days))

                                                @foreach ($days as $d)
                                                    <option value="{{ $d }}">{{ $d }}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <div class="bg-white">
                                            <p>Total Hours:</p>
                                            <input type="hidden" name="TotalHours" id="TotalHoursInput" value="">
                                            <h5 id="TotalHours">0</h5>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" name="resort_id" value="{{$resort_id}}" >

                    </div>

                    <hr class="mt-md-1 mb-md-3">

                    <div class="mb-md-5 mb-4 text-end">
                        <button  type="Submit" class="btn btn-themeBlue btn-sm" type="submit">Submit</button>
                    </div>

                </form>
                    <div class="card bg mt-4">
                        <div class="card-header">
                            <div class="row g-md-3 g-2 align-items-center">
                                <div class="col-xl-3 col-lg-5 col-md-8 col-sm-8 ">
                                    <div class="input-group">
                                        <input type="search" class="form-control " placeholder="Search" />
                                        <i class="fa-solid fa-search"></i>
                                    </div>
                                </div>
                                <div class="col-xl-2 col-md-4 col-sm-4 col-6">
                                    <input type="text" class="form-control " placeholder="Management">
                                </div>
                                <div class="col-xl-2 col-md-4 col-sm-4 col-6">
                                    <select class="form-select" name="Poitions" id="Poitions">
                                        <option ></option>
                                        @if($ResortPosition->isNotEmpty())

                                            @foreach ($ResortPosition as $p)
                                                <option value="{{ $p->id }}">{{ ucfirst($p->position_title) }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                                <div class="col-xl-2 col-md-4 col-sm-4 col-6">
                                    <input type="text" class="form-control datepicker" id="txt-bod"
                                        placeholder="Select Duration">
                                </div>
                                <div class="col-auto ms-auto">
                                    <a href="javascript:void(0)" class="btn btn-weekly active">Weekly</a>
                                    <a href="javascript:void(0)" class="btn btn-monthly ">Monthly</a>
                                </div>
                            </div>
                        </div>
                        <div class="weekly-main">
                            <div class="table-responsive mb-4">
                                <table id="createDutyWeeklyTable" class="table table-createDutyWeekly mb-1">
                                    <thead>
                                        <tr>
                                            <th>Employee Name</th>
                                            @foreach ($headers as $d)
                                                <th>{{ $d['date'] }} <span>{{ $d['day'] }}</span></th>
                                            @endforeach
                                            <th>Summary</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if($Rosterdata->isNotEmpty())
                                            @foreach ($Rosterdata as $r)
                                                <tr>
                                                    <td>
                                                        <div class="createDuty-user">
                                                            <div class="img-circle">
                                                                <img src="{{ Common::getResortUserPicture($r->Parentid) }}" alt="user">
                                                            </div>
                                                            <div>
                                                                <p>
                                                                    <span class="fw-600">{{ ucfirst($r->first_name .' '. $r->last_name) }}</span>
                                                                    <span class="badge badge-white">{{ $r->Emp_id }}</span>
                                                                </p>
                                                                <span>{{ ucfirst($r->position_title) }}</span>
                                                            </div>
                                                        </div>
                                                    </td>

                                                    @php

                                                        $RosterInternalData = Common::GetRosterdata($resort_id, $r->duty_roster_id, $r->emp_id, $WeekstartDate, $WeekendDate,"weekly");
                                                        $totalHours = 0;
                                                        $dataCount = $RosterInternalData->count();
                                                        $minColumns = 7; // Minimum number of columns to account for all days of the week
                                                    @endphp

                                                    @foreach ($headers as $header)
                                                        @php
                                                            $shiftData = $RosterInternalData->firstWhere('date', date('Y-m-d', strtotime($header['date'])));
                                                            $toatalHoursForDay = 0;
                                                            if ($shiftData) {
                                                                $startTime = \Carbon\Carbon::parse($shiftData->StartTime);
                                                                $endTime = \Carbon\Carbon::parse($shiftData->EndTime);

                                                                list($hours, $minutes) = explode(':', $shiftData->DayWiseTotalHours);
                                                                $toatalHoursForDay = (int)$hours + ((int)$minutes / 60); // Convert to decimal hours
                                                                $totalHours += $toatalHoursForDay; // Update total hours worked
                                                            }
                                                        @endphp

                                                        <td>
                                                            <div class="createDuty-tableBlock {{ $shiftData->ShiftNameColor ?? '' }}">
                                                                <div class="d-flex">
                                                                    @if ($shiftData)
                                                                        <div>
                                                                            <p>{{ $startTime->format('h:i A') }} - {{ $endTime->format('h:i A') }}</p>
                                                                            <span>{{ $shiftData->ShiftName }}</span>
                                                                        </div>
                                                                        <div class="badge">{{ $shiftData->DayWiseTotalHours }} {{ $shiftData->color }} hrs</div>
                                                                    @else
                                                                        <div class="createDuty-empty">No Shift Assigned</div>
                                                                    @endif
                                                                </div>
                                                                @if ($shiftData)
                                                                    <div class="d-flex ot-details">
                                                                        @if ($shiftData)
                                                                            <p>OT: {{ $shiftData->OverTime ?? 0 }} hr</p>
                                                                        @endif
                                                                        <p>
                                                                            <button class="editIcon-btn editdutyRoster"
                                                                                    data-date="{{ date('d/m/Y', strtotime($header['date'])) }}"
                                                                                    data-Shift_id="{{ $shiftData->Shift_id ?? '' }}"
                                                                                    data-OverTime="{{ $shiftData->OverTime ?? 0 }}"
                                                                                    data-DayOfDate="{{ $shiftData->DayOfDate ?? '' }}"
                                                                                    data-Attd_id="{{ $shiftData->Attd_id ?? '' }}"
                                                                                    data-DayWiseTotalHours="{{ $shiftData->DayWiseTotalHours ?? '' }}">
                                                                                <i class="fa fa-edit"></i>
                                                                            </button>
                                                                        </p>
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </td>
                                                    @endforeach



                                                    <td>Total Hrs: <span>{{  $totalHours }}</span></td>
                                                </tr>
                                            @endforeach
                                        @else
                                            <tr>
                                                <td colspan="{{ count($headers) + 2 }}" style="text-align: center">No Records Found..</td>
                                            </tr>
                                        @endif
                                    </tbody>
                                </table>

                            </div>
                            {{-- <nav aria-label="Page navigation example">
                                <ul class="pagination justify-content-end">
                                    <li class="page-item "><a class="page-link" href="#"><i
                                                class="fa-solid fa-angle-left"></i></a>
                                    </li>
                                    <li class="page-item active"><a class="page-link" href="#">1</a></li>
                                    <li class="page-item"><a class="page-link" href="#">2</a></li>
                                    <li class="page-item"><a class="page-link" href="#">3</a></li>
                                    <li class="page-item"><a class="page-link" href="#"><i
                                                class="fa-solid fa-angle-right"></i></a>
                                    </li>

                                </ul>

                            </nav> --}}
                            <div class="pagination-custom"> {{ $Rosterdata->links() }}</div>

                        </div>
                        <div class="monthly-main  d-none">
                            <div class="table-responsive mb-4">
                                <table id="" class="table table-bordered table-createDutymonthly mb-1">


                                    <thead>
                                        <tr>
                                            <th> Employee Name</th>
                                            @if(!empty($monthwiseheaders))
                                            @foreach ($monthwiseheaders as $h)
                                                <th>{{ $h['day'] }} <span>{{ $h['dayname'] }}</span></th>

                                            @endforeach
                                            <th class="col-total"><span class="badge badge-p">P</span></th>
                                            <th class="col-total"><span class="badge bg-themeDanger">A</span></th>
                                            <th class="col-total"><span class="badge bg-themeSkyblue">B</span></th>
                                            <th class="col-total"><span class="badge bg-themeGreen">D</span></th>
                                            @endif
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if($Rosterdata->isNotEmpty())
                                        @foreach ($Rosterdata as $r)
                                            <tr>
                                                <td>
                                                    <div class="createDuty-user">
                                                        <div class="img-circle">
                                                            <img src="{{ Common::getResortUserPicture($r->Parentid) }}" alt="user">
                                                        </div>
                                                        <div>
                                                            <p>
                                                                <span class="fw-600">{{ ucfirst($r->first_name .' '. $r->last_name) }}</span>
                                                                <span class="badge badge-white">{{ $r->Emp_id }}</span>
                                                            </p>
                                                            <span>{{ ucfirst($r->position_title) }}</span>
                                                        </div>
                                                    </div>
                                                </td>

                                                @php
                                                    $RosterInternalDataMonth = Common::GetRosterdata($resort_id, $r->duty_roster_id, $r->emp_id, $WeekstartDate, $WeekendDate, "Monthwise");
                                                @endphp

                                                @foreach ($monthwiseheaders as $h)
                                                    @php

                                                        $date = date('Y-m') . '-' . str_pad($h['day'], 2, '0', STR_PAD_LEFT); // Example: '2024-12-01'
                                                        $shiftData = $RosterInternalDataMonth->firstWhere('date', $date);

                                                    @endphp

                                                    <td>
                                                        @if ($shiftData)
                                                            @if($shiftData->Status =="Present")
                                                               {{ $shiftData->LeaveFirstName }}</div>
                                                            @else
                                                            <span class="badge" style="background-color:{{ $shiftData->LeaveColor }}">{{ $shiftData->LeaveFirstName }}</span>
                                                            @endif
                                                        @else
                                                           -
                                                        @endif
                                                    </td>
                                                @endforeach


                                            </tr>
                                        @endforeach
                                    @endif

                                    </tbody>
                                </table>
                            </div>
                            <div class="pagination-custom">
                                <nav aria-label="Page navigation example">
                                    {{ $Rosterdata->links() }}
                                </nav>
                            </div>


                        </div>

                    </div>

                </div>
            </div>

        </div>
    </div>

    <div class="modal fade" id="editdutyRoster-modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="staticBackdropLabel">Edit Duty Roster</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="UpdateDutyRoster">
                    @csrf
                    <div class="modal-body">
                        <div class="row mt-3">
                            <div class="col-md-12 mt-3">
                                <lable>Shift Date </lable>
                                <input type="text" readonly class="form-control" id="shiftdate" name="shiftdate" placeholder="Shift Date">
                            </div>

                            <div class="col-md-12 mt-3">
                                <lable>Shift </lable>
                                <select class="form-select select2t-none" id="Shiftpopup"  aria-label="Default select example" name="Shiftpopup">
                                    <option></option>
                                    @if($ShiftSettings->isNotEmpty())
                                        @foreach ($ShiftSettings as $s)

                                            <option value="{{ $s->id }}"  data-totalHrs="{{ $s->TotalHours }}"> {{ ucfirst($s->ShiftName) }}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>

                            <div class="col-md-12 mt-3 ">
                                <lable>Over Time </lable>
                                <input type="text" class="form-control ShiftOverTime" id="ShiftOverTime" name="ShiftOverTime" placeholder="Over Time">
                            </div>
                            <div class="col-md-12 mt-3">
                                <select class="form-select select2t-none DayOfDate" id="DayOfDateModel"aria-label="Default select example" name="DayOfDateModel">
                                    <option selected value=""></option> <!-- Leave this blank for the placeholder -->
                                    @if(!empty($days))

                                        @foreach ($days as $d)
                                            <option value="{{ $d }}">{{ $d }}</option>
                                        @endforeach
                                    @endif
                                </select>

                            </div>

                            <div class="col-12 mt-3">
                                <hr class="mt-0 ">
                                <div class="bg-white text-end">
                                    <p>Total Hours:</p>
                                    <input type="hidden" name="TotalHoursModel" id="TotalHoursModelInput" value="">
                                    <h5 id="TotalHoursModel">0</h5>
                                </div>
                            </div>
                            <input type="hidden" id="Attd_id" name="Attd_id">
                        </div>

                    </div>
                    <div class="modal-footer justify-content-center">
                        <a href="javascript:void(0)" data-bs-dismiss="modal" class="btn btn-themeGray ms-auto">Cancel</a>
                        <button type="submit"   class="btn btn-theme" >Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endsection

@section('import-css')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

@endsection

@section('import-scripts')
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<script type="text/javascript">
    // new DataTable('#example');



    // tooltip
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });


    // date range picker
    $(document).ready(function ()
    {
        flatpickr(".overtime", {
            enableTime: true,
            noCalendar: true,
            dateFormat: "h:i", // 12-hour format without AM/PM
            time_24hr: false,  // Ensures 12-hour format
            minuteIncrement: 1, // Allows 1-minute steps

        });
        var shiftOverTimePicker =  flatpickr(".shiftdate", {
            enableTime: true,
            noCalendar: true,
            dateFormat: "h:i", // 12-hour format without AM/PM
            time_24hr: false,  // Ensures 12-hour format
            minuteIncrement: 1, // Allows 1-minute steps

        });
        $('#Employee').select2({
            placeholder: "Select an Employee", // Placeholder text
            allowClear: true // Adds a clear (X) button to reset the dropdown
        });
        $('#Shift').select2({
            placeholder: "Select a Shift", // Placeholder text
            allowClear: true // Adds a clear (X) button to reset the dropdown
        });
        $('.DayOfDate').select2({
            placeholder: "Select a Day Off", // Placeholder text
            allowClear: true // Adds a clear (X) button to reset the dropdown
        });
        $('#Poitions').select2({
            placeholder: "Select a Poitions", // Placeholder text
            allowClear: true // Adds a clear (X) button to reset the dropdown
        });
        $('#Shiftpopup').select2({
            placeholder: "Select a Shift", // Placeholder text
            allowClear: true // Adds a clear (X) button to reset the dropdown
        });

        $('.data-Table').dataTable({
            "searching": false,
            "bLengthChange": false,
            "bFilter": true,
            "bInfo": false,
            "bAutoWidth": false,
            scrollX: true,
            "iDisplayLength": 10,
        });
        $('.datepicker').datepicker({});
        $("#hiddenInput").daterangepicker({
            autoApply: true,
            startDate: moment(),
            endDate: moment().add(7, 'days'),
            opens: 'right',
            parentEl: '#datapicker',
            alwaysShowCalendars: true,
            linkedCalendars: false
            }, function (start, end) {
                updateDateText(start, end);
        });
        $("#hiddenInput").data('daterangepicker').show();
            function updateDateText(start, end) {
                let startDate = start.format("YYYY-MM-DD").toString();
                let endDate = end.format("YYYY-MM-DD").toString();

                let startDate1 = start.format("DD MMM").toString();
                let endDate1 = end.format("DD MMM").toString();

                $("#MakeShift").val(startDate1 + " - " + endDate1);



                let enabledDates = [];
                let startDate12 = new Date(startDate);
                let endDate12 = new Date(endDate);

                while (startDate12 <= endDate12) {
                        enabledDates.push(startDate12.toISOString().split('T')[0]);  // Store the formatted date (YYYY-MM-DD)

                        // Increment the date by one day
                        startDate12.setDate(startDate12.getDate() + 1);
                    }
             $('#MakeShift').datepicker('destroy');  // Destroy the previous datepicker instance

            // Initialize DatePicker with enabled dates
                $('#MakeShift').datepicker({
                    beforeShowDay: function (date) {
                        const formattedDate = date.toISOString().split('T')[0];
                        return enabledDates.includes(formattedDate);
                    }
                });

            }
        $("#hiddenInput").on('apply.daterangepicker', function (ev, picker) {
            picker.show();
        });

        $('#DutyRosterForm').validate({
            rules: {
                Emp_id: {
                    required: true,
                },
                MakeShift:{
                    required: true,
                },
                Shift:{
                    required: true,
                },
                DayOfDate:{
                    required: true,
                }

            },
            messages: {
                Emp_id: {
                    required: "Please Select Employee.",
                },
                MakeShift:{
                    required:"Please select Shift Date.",
                },
                Shift:{
                    required: "Please select Shift .",
                },
                DayOfDate:{
                    required: "Please select Day Off.",
                }
            },
            errorPlacement: function(error, element) {

                if (element.is(':radio') || element.is(':checkbox')) {
                    error.insertAfter(element.closest('div'));
                } else {
                    var nextElement = element.next('span');
                    if (nextElement.length > 0) {
                        error.insertAfter(nextElement);
                    } else {
                        error.insertAfter(element);
                    }
                }
            },
            submitHandler: function(form) {
                var formData = new FormData(form); // Use FormData to handle file inputs

                $.ajax({
                    url: "{{ route('resort.timeandattendance.StoreDutyRoster') }}", // Ensure route is correct
                    type: "POST",
                    data: formData,
                    contentType: false,  // Required for file uploads
                    processData: false,  // Required for file uploads
                    success: function(response) {
                        if (response.success) {


                            $('#sendReminder-modal').modal('hide');
                            toastr.success(response.msg, "Success", {
                                positionClass: 'toast-bottom-right'
                            });
                            setTimeout(function() {
                                window.location.reload();
                            }, 3000);
                        } else {
                            toastr.error(response.msg, "Error", {
                                positionClass: 'toast-bottom-right'
                            });
                        }
                    },
                    error: function(response) {
                        if (response.status === 422)
                        {
                                var errors = response.responseJSON.errors; // Access error object
                                var errs = '';
                                $.each(errors, function (field, messages) {
                                    $.each(messages, function (index, message) {
                                        errs += message + '<br>'; // Append each message
                                    });
                                });
                                toastr.error(errs, "Validation Error", {
                                    positionClass: 'toast-bottom-right'
                                });
                        }
                        else
                        {
                                toastr.error("An unexpected error occurred.", "Error", {
                                    positionClass: 'toast-bottom-right'
                                });
                        }
                    }
                });
            }
        });

        $(document).on("click", ".editdutyRoster", function() {

            let date = $(this).attr('data-date');
            let Shift_id = $(this).attr('data-Shift_id');
            let overtime = $(this).attr('data-OverTime');
            let Attd_id = $(this).attr('data-Attd_id');
            let DayWiseTotalHours = $(this).attr('data-DayWiseTotalHours');
            $("#shiftdate").val(date);
            $("#Shiftpopup").val(Shift_id).trigger('change');
            $("#ShiftOverTime").val(overtime);
            let DayOfDate = $(this).attr('data-DayOfDate');
            $("#DayOfDateModel").val(DayOfDate).trigger('change');
            $("#Attd_id").val(Attd_id);

            if (!$("#ShiftOverTime").data("flatpickr")) {
                flatpickr("#ShiftOverTime", {
                    enableTime: true,
                    noCalendar: true,
                    dateFormat: "h:i", // 12-hour format without AM/PM
                    time_24hr: false,  // Ensures 12-hour format
                    minuteIncrement: 1, // Allows 1-minute steps
                });
            }
            $("#ShiftOverTime").val(overtime);

            $("#ShiftOverTime")[0]._flatpickr.setDate(overtime, false);
            $("#editdutyRoster-modal").modal('show');
            $("#ShiftOverTime").attr('data-DayWiseTotalHours', DayWiseTotalHours);
            calculateTotalTime(overtime,DayWiseTotalHours,flag="Modal");

        });

        $('#UpdateDutyRoster').validate({
                rules: {
                    shiftdate: {
                        required: true,
                    },
                    Shiftpopup: {
                        required: true,
                    }
                   ,
                    DayOfDateModel: {
                        required: true,
                    }
                },
                messages :
                {
                    shiftdate: {
                        required: "Please Add Shift time",
                    },
                    Shiftpopup: {
                        required: "Please Select Shift ",
                    }
                    ,
                    DayOfDateModel: {
                        required: "Please Select Day Off ",
                    }
                },
                errorPlacement: function(error, element) {

                    if (element.is(':radio') || element.is(':checkbox')) {
                        error.insertAfter(element.closest('div'));
                    } else {
                        var nextElement = element.next('span');
                        if (nextElement.length > 0) {
                            error.insertAfter(nextElement);
                        } else {
                            error.insertAfter(element);
                        }
                    }
                },
                submitHandler: function(form) {
                    var formData = new FormData(form);

                    $.ajax({
                        url: "{{ route('resort.timeandattendance.UpdateDutyRoster') }}",
                        type: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            $('#respond-rejectModal').modal('hide');
                            if (response.success)
                            {

                                toastr.success(response.message, "Success",
                                        {
                                            positionClass: 'toast-bottom-right'
                                        });
                                $("#editdutyRoster-modal").modal('hide')
                                setTimeout(function() {
                                    window.location.reload();
                                }, 3000);
                            }
                            else
                            {
                                toastr.error(response.message,"error", { positionClass: 'toast-bottom-right'});

                            }

                    },
                        error: function(response) {
                            var errors = response.responseJSON;
                            var errs = '';
                            console.log(errors.errors);
                            $.each(errors.errors, function(key, error)
                            {
                                console.log(error);
                                errs += error + '<br>';
                            });
                            toastr.error(errs, { positionClass: 'toast-bottom-right'});
                        }
                    });
                }
        });
    });
    $('.addOvertime-modal').on('click', function() {

        let makeshiftdate = $("#MakeShift").val();
        if(makeshiftdate=="")
        {
            toastr.error("Please Select Calander", "Error", {
                positionClass: 'toast-bottom-right'
            });
            return false;
        }
        let overtime = "00:00";
        $('.overtime').toggleClass('OverTimeInput');
        totalHrs = $(this).data('totalhrs');
        $('.overtime').val(totalHrs);

    });
    $(document).on("change", ".overtime", function() {
        let overtime = $(this).val(); // Get the overtime time (HH:MM)
        let DayWiseTotalHours="";
        calculateTotalTime(overtime,DayWiseTotalHours);
        // Validate the overtime format (HH:MM)
    });
    $(document).on("change", "#ShiftOverTime", function() {
        let overtime = $(this).val(); // Get the overtime time (HH:MM)
        let DayWiseTotalHours=$(this).attr('data-DayWiseTotalHours');
        calculateTotalTime(overtime,DayWiseTotalHours,flag="Modal");
    });
    $(document).on("change", "#Shift", function() {
        let overtime = "00:00";
        let DayWiseTotalHours="";
        calculateTotalTime(overtime,DayWiseTotalHours);
    });
    $(".btn-weekly").click(function () {
        $(this).addClass("active");
        $(".weekly-main").addClass("d-block");
        $(".weekly-main").removeClass("d-none");
        $(".btn-monthly").removeClass("active");
        $(".monthly-main").addClass("d-none");
        $(".monthly-main").removeClass("d-block");
    });
    $(".btn-monthly").click(function () {
        $(this).addClass("active");
        $(".monthly-main").addClass("d-block");
        $(".monthly-main").removeClass("d-none");
        $(".btn-weekly").removeClass("active");
        $(".weekly-main").addClass("d-none");
        $(".weekly-main").removeClass("d-block");
    });
    $(document).on('click', '.addMore-addOvertime', function (e) {
        e.preventDefault();

        // Clone the first shift-block
        var newBlock = $('.addOvertime-block').first().clone();

        // Reset the select dropdowns to their default state (first option)
        newBlock.find('select').each(function () {
            $(this).prop('selectedIndex', 0); // Reset to first option
        });
        // Clear input values from cloned block
        newBlock.find('input').val('');

        // Append the new block
        $('.addOvertime-main').append(newBlock);
    });
    $(document).on('change', '#Employee', function (e) {

        let id = $(this).val();

        $.ajax({
                url: "{{ route('resort.timeandattendance.DutyRosterandLeave') }}",
                type: "POST",
                data: {"_token":"{{ csrf_token() }}","id":id},
                success: function (response) {

                    if (response.success) {

                        $(".createduty-Append").html(response.view);

                        toastr.success(response.message, "Success", {
                            positionClass: 'toast-bottom-right'
                        });
                    } else {
                        toastr.error(response.message, "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                },
                error: function(response) {
                    var errors = response.responseJSON;
                    var errs = '';
                    $.each(errors.errors, function(key, error) { // Adjust according to your response format
                        errs += error + '<br>';
                    });
                    toastr.error(errs, { positionClass: 'toast-bottom-right' });
                }
            });


    });
    $(document).on('click', '#DefaultShiftTime', function () {
        if ($(this).prop('checked')) {

            $("#MakeShift").attr('disabled',true);

            $("#MakeShift").val($("#hiddenInput").val());
        }
        else
        {
            $("#MakeShift").attr('disabled',false);
        }
    });
    function calculateTotalTime(overtime,DayWiseTotalHours,flag="")
    {
        if(overtime == "")
        {
            overtime = "00:00";
        }
        if (!/^([0-9]{1,2}):([0-9]{2})$/.test(overtime)) {
            toastr.error("Please enter a valid overtime value in HH:MM format.", "Error", {
                positionClass: 'toast-bottom-right'
            });
            return;
        }

        // Split the overtime input into hours and minutes
        let [hours, minutes] = overtime.split(':');
        hours = parseInt(hours);
        minutes = parseInt(minutes);

        let totalHrs = "";
        if (DayWiseTotalHours !== "")
        {
            totalHrs = DayWiseTotalHours; // Use provided DayWiseTotalHours
        }
        else
        {
            totalHrs = $("#Shift").find(":selected").data('totalhrs') || "00:00"; // Default to "00:00" if data attribute is missing
        }
        let [shiftHours, shiftMinutes] = totalHrs.split(':');
        shiftHours = parseInt(shiftHours);
        shiftMinutes = parseInt(shiftMinutes);
        let shiftTotalHrs = shiftHours + (shiftMinutes / 60);
        if ($.isNumeric(hours) && $.isNumeric(minutes) && $.isNumeric(shiftTotalHrs))
        {
            let totalHours = Math.floor(shiftTotalHrs); // Get the hour part
            let totalMinutes = (shiftTotalHrs - totalHours) * 60; // Convert decimal minutes back to actual minutes


            totalHours += hours;
            totalMinutes += minutes;

            // Adjust for overflow of minutes (60 minutes = 1 hour)
            if (totalMinutes >= 60) {
                totalHours += Math.floor(totalMinutes / 60);
                totalMinutes = totalMinutes % 60; // Remaining minutes after converting to hours
            }

            // Format the result as "HH:MM"
            let updatedTotalHrs = `${totalHours.toString().padStart(2, '0')}:${totalMinutes.toString().padStart(2, '0')}`;

            // Display the updated total hours
            if(flag == "Modal")
            {
                $("#TotalHoursModelInput").val(updatedTotalHrs);
                $("#TotalHoursModel").html(updatedTotalHrs);
            }
            else
            {
                $("#TotalHoursInput").val(updatedTotalHrs);
                $("#TotalHours").html(updatedTotalHrs);
            }
        }
        else
        {
            toastr.error("Please enter a valid overtime value.", "Error", {
                positionClass: 'toast-bottom-right'
            });
        }
    }
</script>
@endsection

