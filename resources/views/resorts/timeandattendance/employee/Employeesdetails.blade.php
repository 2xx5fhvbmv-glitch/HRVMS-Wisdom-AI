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
            <div class="page-hedding page-appHedding">
                <div class="row justify-content-between g-md-2 g-1">
                    <div class="col-auto">
                        <div class="page-title">
                            <span>Time And Attendance</span>
                            <h1>{{ $page_title }}</h1>
                        </div>
                    </div>
                </div>
            </div>


            <div class="card replacheDetailsCard">
                <div class="card-header">
                    <div class="row g-md-3 g-2 align-items-center">
                        <div class="col-lg">
                            <div class="empDetails-user">
                                <div class="img-circle"><img src="{{   $employee->profile_picture}}" alt="user">
                                </div>
                                <div>
                                    <h4>{{$employee->name }} <span class="badge badge-themeNew">{{ $employee->Emp_Code }}</span></h4>
                                    <p>{{$employee->Position }} ( {{$employee->department }})</p>
                                </div>
                            </div>
                        </div>
                        <form action="{{ route('resort.timeandattendance.EmpDetailsPrint') }}" method="post"  id="EmpDetailsPrint">
                            @csrf
                            <div class="col-xl-3 col-lg-4 col-md-5 col-sm-6 col ms-auto">
                                <div class="dateRangeAb" id="datapicker">
                                    <div>
                                        <!-- Hidden input field to attach the calendar to -->
                                            @php
                                                use Carbon\Carbon;

                                                $firstDay = Carbon::now()->startOfMonth()->format('d/m/Y');
                                                $lastDay = Carbon::now()->format('d/m/Y'); // Today (default: 1st of month to today)
                                                $currentMonthRange = $firstDay . '-' . $lastDay;
                                            @endphp

                                            <input type="text" class="form-control" name="hiddenInput" id="hiddenInput" value="{{ $currentMonthRange }}">
                                                                                </div>
                                    <p id="startDate" class="d-none">Start Date:</p>
                                    <p id="endDate" class="d-none">End Date:</p>
                                </div>
                            </div>
                            <div class="col-auto">
                                <button  type="submit" class="btn btn-themeSkyblue btn-sm">Download</button>
                            </div>
                          <input type="hidden" name="emp_id" value="{{ $employee->emp_id }}">

                        </form>
                    </div>
                </div>
                <div  id="tablePrint">
                    <div class="row g-lg-4 g-3 mb-4">
                        <div class="col-xxl-cust5 col-xl-3 col-lg-4 col-sm-6">
                            <div class="empDetail-block">
                                <div>
                                    <h6>Present Days</h6>
                                    <strong>{{ $employee->PresentCount }}</strong>
                                </div>
                                <div>
                                    {{-- <a href="#">
                                        <img src="{{ URL::asset('resorts_assets/images/arrow-right-circle.svg')}}" alt="" class="img-fluid">
                                    </a> --}}
                                </div>
                            </div>
                        </div>
                        <div class="col-xxl-cust5 col-xl-3 col-lg-4 col-sm-6">
                            <div class="empDetail-block">
                                <div>
                                    <h6>Absent Days</h6>
                                    <strong>{{ $employee->AbsentCount }}</strong>
                                </div>
                                <div>
                                    {{-- <a href="#">
                                        <img src="{{ URL::asset('resorts_assets/images/arrow-right-circle.svg')}}" alt="" class="img-fluid">
                                    </a> --}}
                                </div>
                            </div>
                        </div>
                        <div class="col-xxl-cust5 col-xl-3 col-lg-4 col-sm-6">
                            <div class="empDetail-block">
                                <div>
                                    <h6>Total Hours Worked</h6>
                                    <strong>{{ $employee->TotalHoursWorked }}</strong>
                                </div>
                                <div>
                                    {{-- <a href="#">
                                        <img src="{{ URL::asset('resorts_assets/images/arrow-right-circle.svg')}}" alt="" class="img-fluid">
                                    </a> --}}
                                </div>
                            </div>
                        </div>
                        <div class="col-xxl-cust5 col-xl-3 col-lg-4 col-sm-6">
                            <div class="empDetail-block">
                                <div>
                                    <h6>OT Hours</h6>
                                    <strong>{{ $employee->TotalOverTime }}</strong>
                                </div>
                                <div>
                                    {{-- <a href="#">
                                        <img src="{{ URL::asset('resorts_assets/images/arrow-right-circle.svg')}}" alt="" class="img-fluid">
                                    </a> --}}
                                </div>
                            </div>
                        </div>
                        <div class="col-xxl-cust5 col-xl-6 col">
                            <div class="empDetail-block empDetailPro-block">
                                <div>
                                    <div class="progress progress-custom progress-themeskyblue">
                                        <div class="progress-bar" role="progressbar" style="width: {{$employee->onTimePercentage }}" aria-valuemin="0" aria-valuemax="100">
                                            {{ $employee->onTimePercentage }}%

                                        </div>

                                    </div>
                                    <span>On Time</span>
                                </div>
                                <div>
                                    <div class="progress progress-custom progress-themeDanger">
                                        <div class="progress-bar" role="progressbar" style="width: {{ $employee->LatePercentage}}" aria-valuemin="0" aria-valuemax="100">
                                            {{$employee->LatePercentage }}%

                                        </div>
                                    </div>
                                    <span>Late Coming</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="empDetails-leave mb-4">
                        <div class="card-title">
                            <div class="row g-2 align-items-center">
                                <div class="col">
                                    <h3>Leave Balance</h3>
                                </div>
                                <!-- <div class="col-auto"><span class="badge badge-themeNew">Total:{{ $TotalSum }}</span></div> -->
                            </div>
                        </div>
                        <div class="row  gx-xl-5  gx-4">
                            @php
                                $chunkedLeaveCategories = $leave_categories->chunk(4);
                            @endphp

                            @foreach($chunkedLeaveCategories as $chunk)
                                <div class="col-lg-3 col-sm-6">
                                    <table class="table-leave">
                                        <tbody>
                                            @foreach($chunk as $item)
                                                <tr>
                                                    <th>{{ $item->leave_type }}</th>
                                                    <td>{{ $item->ThisYearOfused_days ?? 0 }}/{{ $item->allocated_days }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endforeach

                        </div>
                    </div>

                    <div class="card-header">
                        <div class="row g-md-3 g-2 align-items-center">
                            <div class="col">
                                <div class="card-title  pb-0 mb-0 border-0">
                                    <h3>Attendance History</h3>
                                </div>
                            </div>
                            <div class="col-auto ms-auto">
                                <a href="javascript:void(0)" class="btn btn-grid"><img src=" {{ URL::asset('resorts_assets/images/grid.svg')}}" alt="icon"></a>
                                <a href="javascript:void(0)" class="btn btn-list active"><img src=" {{ URL::asset('resorts_assets/images/list.svg')}}" alt="icon"></a>
                            </div>
                        </div>
                    </div>
                    <div class="list-main">
                        <div class="table-responsive">
                            <table class="table table-collapseNew table-applicants  " id="EmployeeDetails">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Shift</th>
                                        <th>Check in Time</th>
                                        <th>Check Out Time</th>
                                        <th>Total Hours</th>
                                        <th>Over Time</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>


                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="grid-main d-none">
                        <div class="row g-md-4 g-3 mb-4">

                            @if($AttendanceHistroy->isNotEmpty())
                                @foreach ($AttendanceHistroy as $item)
                                    <div class="col-xxl-cust5 col-xl-3 col-lg-4 col-sm-6">
                                        <div class="empDetailsGrid-block">
                                            <div class="header">
                                                <p><i class="fa-regular fa-calendar"></i>{{ $item->date }}</p>

                                                    {!!  $item->Status !!}
                                            </div>
                                            <div class="time">
                                                <div>
                                                    <div class="label">Check In</div>
                                                    <h6>{{ $item->CheckInTime }}</h6>
                                                </div>
                                                <div>
                                                    <div class="label">Check Out</div>
                                                    <h6>{{ $item->CheckOutTime }}</h6>
                                                </div>
                                                <div>
                                                    <div class="label">Total</div>
                                                        <h6>{{ $item->DayWiseTotalHours }}hr </h6>
                                                        <p>+{{ (isset($item->OverTime) && $item->OverTime  != '-' ) ? $item->OverTime: '00:00' }}<span class="badge badge-themeWarning">OT</span></p>
                                                    </div>
                                            </div>
                                            <div style="height: 100px; overflow-y: auto; border: 1px solid #ddd; padding: 8px; border-radius: 4px;">
                                                <div class="label">Notes</div>
                                                <p>{{ isset($item->note) ?  $item->note  : "No notes found.." }}</p>
                                            </div>
                                            <div class="text-center">
                                                <a href="javascritp:void(0)" class="btn btn-themeBlue btn-sm LocationHistoryData" data-location ="{{ $item->InTime_Location }}" data-id="{{ $item->id }}"><i class="fa-regular fa-location-dot"></i></a>
                                                <a href="javascritp:void(0)" class="btn btn-themeSkyblue btn-sm edit-row-btn" data-note="{{ $item->note }}" data-CheckInTime="{{ $item->CheckInTimeOne }}"  data-CheckOutTime="{{ $item->CheckOutTimeOne }}"  data-OverTime="{{ $item->OverTime }}" data-id="{{ base64_encode($item->Child_id) }}" data-ParentAttd_id="{{ base64_encode($item->ParentAttd_id) }}"  > Edit</a>
                                            </div>

                                        </div>
                                    </div>
                                @endforeach
                                @else
                                        <div class="col-md-12">
                                            <p style="text-align: center"> No Records Found.. </p>
                                            <hr>
                                        </div>

                            @endif

                        </div>
                        <div class="pagination-custom">
                            <nav aria-label="Page navigation example">
                                {!! $AttendanceHistroy->appends(['view' => request('view', 'grid')])->links('pagination::bootstrap-4') !!}

                            </nav>
                        </div>


                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="viewMapLocationHistory-modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="staticBackdropLabel">Map View</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body ">

                    <iframe  width="1075" height="450" style="border:0;" id="ModalIframe" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>

                 </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="AttandanceHistory-modal" tabindex="-1" aria-labelledby="exampleModalLabel"  aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-small">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="staticBackdropLabel">Attendance Histroy</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id='AttandanceHistoryForm'>
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="" class="form-label ">Check in Time </label>
                            <input type="text" class="form-control" name="CheckingTime" id="CheckingTime" placeholder="Check In Time">
                        </div>
                        <div class="mb-3">
                            <label for="" class="form-label ">Check Out Time </label>
                            <input type="text" class="form-control" name="CheckingOutTime" id="CheckingOutTime" placeholder="Check Out Time">
                        </div>
                        <div class="mb-3">
                            <label for="" class="form-label ">OverTime </label>
                            <input type="text" class="form-control overtime" name="OverTime" id="OverTime" placeholder="Over Time">
                        </div>
                        <div class="mb-3">
                            <label for="" class="form-label ">Notes </label>
                            <textarea class="form-control Notes" name="notes" id="notes" placeholder="Notes"></textarea>
                        </div>
                        <div style="height:180px;"></div>
                        <input type="hidden" name="attandance_id" id="attandance_id">
                        <input type="hidden" name="ParentAttd_id" id="ParentAttd_id">

                    </div>
                    <div class="modal-footer">
                        <a href="#" data-bs-dismiss="modal" class="btn btn-themeGray ms-auto">Cancel</a>
                        <button type="submit" class="btn btn-themeBlue">Submit</button>
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
    $(document).ready(function() {

        const urlParams = new URLSearchParams(window.location.search);
        const currentView = urlParams.get('view')  // Default to 'list'
        // Initialize the view on page load
        setView(currentView);
         // Initialize the Date Range Picker


        $("#hiddenInput").daterangepicker({
            autoApply: true,
            startDate: moment().startOf('month'),
            endDate: moment(),
            opens: 'right',
            parentEl: '#datapicker',
            alwaysShowCalendars: true,
            linkedCalendars: false,
            locale: {
                format: 'DD/MM/YYYY',
                separator: '-'  // Match the separator in your date string
            }
        });
        // Toggle functionality
        let isPickerVisible = false; // Track visibility state

        $("#hiddenInput").on('click', function () {
            const picker = $(this).data('daterangepicker'); // Get the picker instance

            if (isPickerVisible) {
                picker.hide(); // Hide the picker if it's visible
            } else {
                picker.show(); // Show the picker if it's hidden
            }

            isPickerVisible = !isPickerVisible; // Toggle the visibility state
        });


            flatpickr(".overtime", {
                enableTime: true,
                noCalendar: true,
                dateFormat: "h:i", // 12-hour format without AM/PM
                time_24hr: false,  // Ensures 12-hour format
                minuteIncrement: 1, // Allows 1-minute steps
            });

            employeedetails();


            $("#hiddenInput").on('change', function(ev, picker) {

                var date = $(this).val();
                var dates = date.split('-');
                console.log(dates);

                var startDate = dates[0].trim();
                var endDate = dates[1].trim();

                var emp_id = "{{base64_encode($employee->emp_id) }}";

                var showGridView = $("#tablePrint .btn-grid").hasClass("active");

               $.ajax({
                   url: "{{ route('resort.timeandattendance.EmpDetailsFilters') }}",
                   method: 'POST',
                   data: {
                       start_date: startDate,
                       end_date: endDate,
                       emp_id: emp_id
                   },
                   success: function(response) 
                   {
                        $("#tablePrint").html(response.html);
                        if (showGridView) {
                            $("#tablePrint .btn-grid").addClass("active");
                            $("#tablePrint .btn-list").removeClass("active");
                            $("#tablePrint .grid-main").removeClass("d-none").addClass("d-block");
                            $("#tablePrint .list-main").addClass("d-none").removeClass("d-block");
                        } else {
                            $("#tablePrint .btn-list").addClass("active");
                            $("#tablePrint .btn-grid").removeClass("active");
                            $("#tablePrint .list-main").removeClass("d-none").addClass("d-block");
                            $("#tablePrint .grid-main").addClass("d-none").removeClass("d-block");
                        }
                   },
                   error: function(xhr, status, error) {
                       // Handle errors
                       console.error('Error fetching data:', error);
                   }
               });
            });
    });

    function setView(view)
    {
        if(view=="grid")
        {
            $(".btn-grid").trigger("click");

        }
        else{
            $(".btn-list").trigger("click");
        }

    }

         $(document).on('click', '#tablePrint .btn-grid', function () {
                $(this).addClass("active");
                $("#tablePrint .grid-main").addClass("d-block").removeClass("d-none");
                $("#tablePrint .btn-list").removeClass("active");
                $("#tablePrint .list-main").addClass("d-none").removeClass("d-block");
            });
            $(document).on('click', '#tablePrint .btn-list', function () {
                $(this).addClass("active");
                $("#tablePrint .list-main").addClass("d-block").removeClass("d-none");
                $("#tablePrint .btn-grid").removeClass("active");
                $("#tablePrint .grid-main").addClass("d-none").removeClass("d-block");
                if ($.fn.DataTable && $.fn.DataTable.isDataTable('#EmployeeDetails')) {
                    $('#EmployeeDetails').DataTable().ajax.reload();
                }
            });
        $(document).on("click", ".LocationHistoryData", function()
        {
            let location1 = $(this).attr('data-location');
            let type =$(this).data('id');
            if (!location1 || location1.trim() === "")
            {
                toastr.error(type+" data not avilable", "Validation Error", {
                    positionClass: 'toast-bottom-right'
                });
                return false;
            }
            $("#ModalIframe").attr("src", location1);
            $("#viewMapLocationHistory-modal").modal('show');
        });


        $(document).on("click", ".edit-row-btn", function()
        {
            let id  = $(this).attr('data-id');
            let CheckInTime  = $(this).attr('data-checkinTime');
            let CheckOutTime  = $(this).attr('data-checkOutTime');
            let OverTime  = $(this).attr('data-overtime');
            let note = $(this).attr('data-note');
            let ParentAttd_id = $(this).attr('data-ParentAttd_id');

            $("#AttandanceHistory-modal").modal('show');
            $("#attandance_id").val(id);
            $("#OverTime").val(OverTime);
            $("#CheckingOutTime").val(CheckOutTime);
            $("#CheckingTime").val(CheckInTime);
            $("#notes").val(note);
            $("#ParentAttd_id").val(ParentAttd_id);

        });


        $('#EmpDetailsPrint').validate({
                rules: {
                    hiddenInput: {
                        required: false,
                    },


                },
                messages: {
                    hiddenInput: {
                        required: "Please Enter Select Date Range.",
                    },

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
                }
            });
            $('#AttandanceHistoryForm').validate({
                rules: {
                    CheckingTime: {
                        required: false,
                    },
                    CheckingOutTime:{
                        required: false,
                    },
                    OverTime:{
                        required: false,
                    }

                },
                messages: {
                    CheckingTime: {
                        required: "Please Enter Checkin Time.",
                    },
                    CheckingOutTime:{
                        required:"Please Enter Check Out Time.",
                    },
                    OverTime:{
                        required: "Please Enter Over Time .",
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
                        url: "{{ route('resort.timeandattendance.updateattandancehistory') }}", // Ensure route is correct
                        type: "POST",
                        data: formData,
                        contentType: false,  // Required for file uploads
                        processData: false,  // Required for file uploads
                        success: function(response) {
                            if (response.success) {


                                $("#AttandanceHistory-modal").modal('hide');
                                toastr.success(response.message, "Success", {
                                    positionClass: 'toast-bottom-right'
                                });
                                setTimeout(function() {
                                    window.location.reload();
                                }, 3000);
                            } else {
                                toastr.error(response.message, "Error", {
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

     function employeedetails()
     {
        var emp_id = "{{ $employee->emp_id }}";
            var urlTemplate = '{{ route("resort.timeandattendance.employee.AttandanceHisotry", ["id" => "__emp_id__"]) }}';

            var url = urlTemplate.replace('__emp_id__', emp_id);

                    $('#EmployeeDetails tbody').empty();

            var HolidayList =   $('#EmployeeDetails').DataTable({
                        searching: false,
                        bLengthChange: false,
                        bFilter: true,
                        bInfo: true,
                        bAutoWidth: false,
                        scrollX: true,
                        iDisplayLength: 6,
                        processing: true,
                        serverSide: true,
                        order: [[7, 'desc']],
                        ajax: {
                            url: url,
                            type: 'GET',
                        },
                        columns: [
                            { data: 'Date', name: 'Date', className: 'text-nowrap' },
                            { data: 'Shift', name: 'Shift', className: 'text-nowrap' },
                            { data: 'CheckInTime', name: 'CheckInTime', className: 'text-nowrap'},
                            { data: 'CheckOutTime', name: 'CheckOutTime', className: 'text-nowrap'},
                            { data: 'TotalHours', name: 'TotalHours', className: 'text-nowrap'},
                            { data: 'OverTime', name: 'OverTime', className: 'text-nowrap'},
                            { data: 'Status', name: 'Status', className: 'text-nowrap'},
                            { data: 'Action', name: 'Action', className: 'text-nowrap'},
                            { data: 'created_at', name: 'created_at', visible: false, searchable: false, orderable: true },

                        ]
                    });
     }
   
   
   </script>
    @endsection
