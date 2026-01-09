<?php $__env->startSection('page_tab_title' , $page_title); ?>

    <?php if($message = Session::get('success')): ?>
        <div class="alert alert-success">
            <p><?php echo e($message); ?></p>
        </div>
    <?php endif; ?>

    <?php $__env->startSection('content'); ?>
    <div class="body-wrapper pb-5">
        <div class="container-fluid">
            <div class="page-hedding">
                <div class="row justify-content-between g-3">
                    <div class="col-auto">
                        <div class="page-title">
                            <span>Time And Attendance</span>
                            <h1><?php echo e($page_title); ?></h1>
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <div class="card ">
                    <form id="OverTimeform" class="<?php if(Common::checkRouteWisePermission('resort.timeandattendance.OverTime',config('settings.resort_permissions.create')) == false): ?> d-none <?php endif; ?>">
                        <div class="row g-xl-4 g-3 mb-3 align-items-end">
                            <div class="col-sm-3 col-md-3">
                                <label for="select-emp" class="form-label">SELECT EMPLOYEE</label>
                                <select class="form-select" name="Emp_id" id="Employee">
                                    <option></option> <!-- Leave this blank for the placeholder -->
                                    <?php if($employees->isNotEmpty()): ?>
                                        <?php $__currentLoopData = $employees; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $e): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <option value="<?php echo e($e->id); ?>"><?php echo e(ucfirst($e->first_name . ' ' . $e->last_name)); ?></option>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <div class="col-sm-3 col-md-3">
                                <input type="text" class="form-control datepicker" name="shiftdate" id="shiftdate" placeholder="Select Date">
                            </div>
                            <div class="col-sm-3 col-md-3">
                                <select class="form-select select2t-none" id="Shift"aria-label="Default select example" name="Shift">
                                    <option></option> <!-- Leave this blank for the placeholder -->
                                    <?php if($ShiftSettings->isNotEmpty()): ?>
                                        <?php $__currentLoopData = $ShiftSettings; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
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
                                            <option value="<?php echo e($s->id); ?>"  data-totalHrs ="<?php echo e($TotalHours); ?>"> <?php echo e(ucfirst($s->ShiftName)); ?></option>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <div class="col-lg-3 col-md-3">
                                <input type="text" class="form-control overtime" name="overtime" id="overtime"placeholder="Add Hours">

                            </div>
                        </div>
                        <div class="card-themeSkyblue text-end"><span class="fw-600" id="TotalHours" >Total OT Hours:0</span>
                        <input type="hidden" name="TotalHoursInput" id="TotalHoursInput">
                        </div>
                        <hr class="mt-md-4 mt-3  mb-2">
                        <div class="text-end"><button type="submit" class="btn btn-themeBlue btn-sm">Submit</button></div>
                    </form>

                    <div class="card bg mt-4">
                        <div class="card-header">
                            <div class="row g-md-3 g-2 align-items-center">
                                <div class="col-xl-3 col-lg-5 col-md-8 col-sm-8 ">
                                    <div class="input-group">
                                        <input type="search" class="form-control search" placeholder="Search" />
                                        <i class="fa-solid fa-search"></i>
                                    </div>
                                </div>
                                
                                <div class="col-xl-2 col-md-4 col-sm-4 col-6">
                                    <select class="form-select" name="Poitions" id="Poitions">
                                        <option ></option>
                                        <?php if($ResortPosition->isNotEmpty()): ?>

                                            <?php $__currentLoopData = $ResortPosition; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <option value="<?php echo e($p->id); ?>"><?php echo e(ucfirst($p->position_title)); ?></option>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        <?php endif; ?>
                                    </select>
                                </div>
                                <div class="col-xl-2 col-md-4 col-sm-4 col-6">
                                    <input type="text" class="form-control datepicker" id="DutyRosterCreateDatePickerFilter" placeholder="Select Duration">
                                </div>
                            </div>
                        </div>
                        <div class="appendData">
                            <div class="monthly-main">
                                <div class="table-responsive mb-4">
                                    <table id="" class="table table-bordered table-overtimemonthly mb-1">
                                        <thead>
                                            <tr>
                                                <th>Employee Name</th>
                                                <?php if(!empty($monthwiseheaders)): ?>
                                                    <?php $__currentLoopData = $monthwiseheaders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $h): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                        <?php
                                                            $currentDate = isset($h['date']) ? $h['date'] : date('Y-m-d', strtotime($h['day']));
                                                            $isPublicHoliday = isset($publicHolidays) && in_array($currentDate, $publicHolidays);
                                                        ?>
                                                        <th class="<?php echo e($isPublicHoliday ? 'public-holiday-header' : ''); ?>"><?php echo e($h['day']); ?> <span><?php echo e($h['dayname']); ?></span></th>
                                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                <?php endif; ?>
                                                <th>Holiday OT</th>
                                                <th>Regular OT</th>
                                                <th>Summary</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if($Rosterdata->isNotEmpty()): ?>
                                            <?php $__currentLoopData = $Rosterdata; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <tr>
                                                    <td>
                                                        <div class="createDuty-user">
                                                            <div class="img-circle">
                                                                <img src="<?php echo e(Common::getResortUserPicture($r->Parentid)); ?>" alt="user">
                                                            </div>
                                                            <div>
                                                                <p>
                                                                    <span class="fw-600"><?php echo e(ucfirst($r->first_name . ' ' . $r->last_name)); ?></span>
                                                                    <span class="badge badge-white"><?php echo e($r->Emp_id); ?></span>
                                                                </p>
                                                                <span><?php echo e(ucfirst($r->position_title)); ?></span>
                                                            </div>
                                                        </div>
                                                    </td>

                                                    <?php
                                                        // Fetch overtime data from employee_overtimes table
                                                        $overtimeData = \App\Models\EmployeeOvertime::with('shift')
                                                            ->where('Emp_id', $r->emp_id)
                                                            ->where('resort_id', $resort_id)
                                                            ->whereBetween('date', [$startOfMonth->format('Y-m-d'), $endOfMonth->format('Y-m-d')])
                                                            ->orderBy('date', 'asc')
                                                            ->orderBy('start_time', 'asc')
                                                            ->get();

                                                        // Group overtime by date
                                                        $overtimeByDate = $overtimeData->groupBy(function($item) {
                                                            return $item->date->format('Y-m-d');
                                                        });

                                                        $totalMonthWiseHours = 0;
                                                        $holidayOtMonthly = 0;
                                                        $regularOtMonthly = 0;

                                                        foreach($overtimeData as $ot) {
                                                            $dayName = $ot->date->format('D');
                                                            list($hours, $minutes) = explode(':', $ot->total_time ?? '0:0');
                                                            $totalOtHours = (int)$hours + ((int)$minutes / 60);

                                                            if($dayName == "Fri" || (isset($publicHolidays) && in_array($ot->date->format('Y-m-d'), $publicHolidays))) {
                                                                $holidayOtMonthly += $totalOtHours;
                                                            } else {
                                                                $regularOtMonthly += $totalOtHours;
                                                            }
                                                            $totalMonthWiseHours += $totalOtHours;
                                                        }
                                                    ?>

                                                    <!-- Loop through each monthwise header for the status per day -->
                                                    <?php $__currentLoopData = $monthwiseheaders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $h): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                        <?php
                                                            $date = isset($h['date']) ? $h['date'] : ($startOfMonth->format('Y-m') . '-' . str_pad($h['day'], 2, '0', STR_PAD_LEFT));
                                                            $isPublicHoliday = isset($publicHolidays) && in_array($date, $publicHolidays);
                                                            $dayOvertimes = $overtimeByDate->get($date, collect());
                                                            $overtimeCount = $dayOvertimes->count();

                                                            // Calculate total overtime for the day
                                                            $dayTotalMinutes = 0;
                                                            $hasPending = false;
                                                            $hasRejected = false;
                                                            $hasApproved = false;

                                                            foreach($dayOvertimes as $ot) {
                                                                list($hours, $minutes) = explode(':', $ot->total_time ?? '0:0');
                                                                $dayTotalMinutes += (int)$hours * 60 + (int)$minutes;

                                                                if($ot->status == 'pending') $hasPending = true;
                                                                if($ot->status == 'rejected') $hasRejected = true;
                                                                if($ot->status == 'approved') $hasApproved = true;
                                                            }

                                                            $dayTotalHours = floor($dayTotalMinutes / 60);
                                                            $dayTotalMins = $dayTotalMinutes % 60;
                                                            $dayTotalTime = sprintf('%02d:%02d', $dayTotalHours, $dayTotalMins);

                                                            // Determine status color priority: pending > rejected > approved
                                                            $statusColor = '';
                                                            if($hasPending) {
                                                                $statusColor = 'status-pending';
                                                            } elseif($hasRejected) {
                                                                $statusColor = 'status-rejected';
                                                            } elseif($hasApproved) {
                                                                $statusColor = 'status-approved';
                                                                    }
                                                                ?>

                                                        <td class="overtime-cell <?php echo e($isPublicHoliday ? 'public-holiday-cell' : ''); ?> <?php if($overtimeCount > 0): ?> has-overtime <?php echo e($statusColor); ?> <?php endif; ?>"
                                                            data-date="<?php echo e($date); ?>"
                                                            data-emp-id="<?php echo e($r->emp_id); ?>"
                                                            data-tooltip="<?php echo e(htmlspecialchars(json_encode($dayOvertimes->map(function($ot) {
                                                                return [
                                                                    'id' => $ot->id,
                                                                    'start_time' => $ot->start_time,
                                                                    'end_time' => $ot->end_time,
                                                                    'total_time' => $ot->total_time,
                                                                    'status' => $ot->status,
                                                                    'shift' => $ot->shift->ShiftName ?? ''
                                                                ];
                                                            })->toArray()))); ?>">
                                                            <?php if($overtimeCount > 0): ?>
                                                                <span class="overtime-total"><?php echo e($dayTotalTime); ?></span>
                                                                <?php else: ?>
                                                                <span class="no-overtime">-</span>
                                                            <?php endif; ?>
                                                        </td>
                                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                    <td><?php echo e($holidayOtMonthly); ?></td>
                                                    <td><?php echo e($regularOtMonthly); ?></td>

                                                    <td> Total Hrs: <span><?php echo e(number_format($totalMonthWiseHours, 2)); ?></span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        <?php endif; ?>

                                        </tbody>
                                    </table>
                                </div>
                                <div class="pagination-custom">
                                    <?php echo $Rosterdata->links('pagination::bootstrap-4'); ?>

                                  </div>
                            </div>
                        </div>

                    </div>

                </div>
            </div>

        </div>
    </div>

    <!-- Edit Overtime Modal -->
    <div class="modal fade" id="editOvertimeModal" tabindex="-1" aria-labelledby="editOvertimeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editOvertimeModalLabel">Edit Overtime</h5>
                    <button type="button" class="btn-close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="saveOvertimeForm">
                    <div class="modal-body">
                        <input type="hidden" id="overtimeModalDate">
                        <input type="hidden" id="overtimeModalEmpId">

                        <div id="overtimeEntriesContainer">
                            <!-- Overtime entries will be added here -->
                        </div>

                        <div class="text-end mt-3">
                            <button type="button" class="btn btn-sm btn-primary" id="addOvertimeEntry">
                                <i class="fa fa-plus"></i> Add Entry
                            </button>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<?php $__env->stopSection(); ?>
<?php $__env->startSection('import-css'); ?>
<style>
    .overtime-cell {
        text-align: center;
        padding: 8px 4px;
        font-weight: 600;
        font-size: 14px;
        color: #333;
        cursor: pointer;
        transition: all 0.2s ease;
        position: relative;
        border: 1px solid #E0E0E0;
        min-height: 50px;
        vertical-align: middle;
    }

    .overtime-cell:hover {
        transform: scale(1.05);
        z-index: 5;
        box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        background-color: #f8f9fa;
    }

    .overtime-cell.has-overtime {
        background-color: #E8F5E9;
    }

    .overtime-cell.status-pending {
        background-color: #FFC107 !important; /* Yellow */
        color: #000;
    }

    .overtime-cell.status-rejected {
        background-color: #F44336 !important; /* Red */
        color: #fff;
    }

    .overtime-cell.status-approved {
        background-color: #4CAF50 !important; /* Green */
        color: #fff;
    }

    .overtime-total {
        font-weight: 700;
        font-size: 14px;
    }

    .no-overtime {
        color: #999;
    }

    /* Tooltip Styles */
    .overtime-tooltip {
        position: fixed;
        background: #2C2C2C;
        color: #fff;
        padding: 14px 16px;
        border-radius: 8px;
        font-size: 13px;
        z-index: 9999;
        pointer-events: none;
        box-shadow: 0 4px 16px rgba(0,0,0,0.3);
        min-width: 200px;
        display: none;
    }

    .overtime-tooltip.show {
        display: block !important;
    }

    .overtime-tooltip::after {
        content: '';
        position: absolute;
        bottom: -8px;
        left: 50%;
        transform: translateX(-50%);
        width: 0;
        height: 0;
        border-left: 8px solid transparent;
        border-right: 8px solid transparent;
        border-top: 8px solid #2C2C2C;
    }

    .overtime-tooltip.arrow-top::after {
        bottom: auto;
        top: -8px;
        border-top: none;
        border-bottom: 8px solid #2C2C2C;
    }

    .overtime-tooltip .tooltip-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 12px;
    }

    .overtime-tooltip .tooltip-date {
        font-weight: 600;
        font-size: 13px;
        color: #fff;
    }

    .overtime-tooltip .tooltip-info {
        margin-top: 0;
        line-height: 1.8;
    }

    .overtime-tooltip .tooltip-info div {
        margin-bottom: 4px;
        color: #fff;
        font-size: 12px;
    }

    .overtime-tooltip .tooltip-info .info-label {
        color: #ccc;
        margin-right: 8px;
    }

    .overtime-tooltip .tooltip-info .info-value {
        color: #fff;
        font-weight: 500;
    }

        .overtime-tooltip .tooltip-info .overtime-value {
            color: #FFA726;
            font-weight: 600;
        }

        .overtime-tooltip .tooltip-info .status-pending {
            color: #FFC107;
            font-weight: 600;
        }

        .overtime-tooltip .tooltip-info .status-rejected {
            color: #F44336;
            font-weight: 600;
        }

        .overtime-tooltip .tooltip-info .status-approved {
            color: #4CAF50;
            font-weight: 600;
        }
</style>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('import-scripts'); ?>

    <script type="text/javascript">

        $(document).ready(function() {
            $('#Employee').select2({
                placeholder: "Select an Employee", // Placeholder text
                allowClear: true // Adds a clear (X) button to reset the dropdown
            });
            $('#Shift').select2({
                placeholder: "Select a Shift", // Placeholder text
                allowClear: true // Adds a clear (X) button to reset the dropdown
            });
            $("#Poitions").select2({
                "placeholder": "Select Position",
                "allowClear": true
            });
            $('.datepicker').datepicker({
                format: 'dd/mm/yyyy',
                autoclose: true,      // Close the picker after selection
                todayHighlight: true  // Highlight today's date
            });
            flatpickr(".overtime", {
                enableTime: true,
                noCalendar: true,
                dateFormat: "h:i", // 12-hour format without AM/PM
                time_24hr: false,  // Ensures 12-hour format
                minuteIncrement: 1, // Allows 1-minute steps
            });

            $('#OverTimeform').validate({
                rules: {
                    Emp_id: {
                        required: true,
                    },
                    shiftdate:{
                        required: true,
                    },
                    Shift:{
                        required: true,
                    },
                    overtime:{
                        required: true,
                    }

                },
                messages: {
                    Emp_id: {
                        required: "Please select employee.",
                    },
                    shiftdate:{
                        required:"Please select shift date.",
                    },
                    Shift:{
                        required: "Please select shift .",
                    },
                    overtime:{
                        required: "Please add over time.",
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
                        url: "<?php echo e(route('resort.timeandattendance.StoreOverTime')); ?>", // Ensure route is correct
                        type: "POST",
                        data: formData,
                        contentType: false,  // Required for file uploads
                        processData: false,  // Required for file uploads
                        success: function(response) {
                            if (response.success) {


                                $('#sendReminder-modal').modal('hide');
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
        });

        $(document).on('keyup', '.search', function() {
            updateFilterWiseTable();
        });
        $(document).on('change', '#Poitions', function() {
            updateFilterWiseTable();
        });

        $(document).on('change', '#DutyRosterCreateDatePickerFilter', function() {
            updateFilterWiseTable();
        });

        document.getElementById('DutyRosterCreateDatePickerFilter').addEventListener('input', function () {
            let rawDate = this.value; // Format: YYYY-MM-DD
            if (rawDate) {
                let parts = rawDate.split('-');
                this.value = `${parts[2]}-${parts[1]}-${parts[0]}`; // Converts to DD-MM-YYYY
            }
        });
        $(document).on("change", "#Shift", function() {
            let overtime = "00:00";
            let DayWiseTotalHours="";
            calculateTotalTime(overtime,DayWiseTotalHours);
            $("#overtime").val("00:00");
        });
        $(document).on("change", ".overtime", function() {
                let overtime = $(this).val(); // Get the overtime time (HH:MM)
                let DayWiseTotalHours="";
                calculateTotalTime(overtime,DayWiseTotalHours);
                // Validate the overtime format (HH:MM)
        });

        function calculateTotalTime(overtime,DayWiseTotalHours,flag="")
        {

            console.log(overtime,DayWiseTotalHours,flag);
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
                    $("#TotalHours").html("Total OT Hours:"+updatedTotalHrs);
                }
            }
            else
            {
                toastr.error("Please enter a valid overtime value.", "Error", {
                    positionClass: 'toast-bottom-right'
                });
            }
        }

        function updateFilterWiseTable()
        {
            var search = $(".search").val();
            var Poitions = $("#Poitions").val();
            var DatePickerFilter = $("#DutyRosterCreateDatePickerFilter").val();

            $.ajax({
                    url: "<?php echo e(route('resort.timeandattendance.OverTimeFilter')); ?>",
                    type: "get",
                    data: {"_token":"<?php echo e(csrf_token()); ?>","search":search,"Poitions":Poitions,"date":DatePickerFilter},
                    success: function (response) {
                        if (response.success)
                        {
                            $(".appendData").html(response.view);
                            initOvertimeTooltips();
                            initOvertimeClickHandlers();
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
                        toastr.error(errs, { positionClass: 'toast-bottom-right' });
                    }
                });
        }

        // Initialize overtime tooltips
        function initOvertimeTooltips() {
            $('.overtime-cell').off('mouseenter mouseleave').on({
                mouseenter: function(e) {
                    let tooltipData = $(this).attr('data-tooltip');
                    if (!tooltipData || tooltipData === '' || tooltipData === 'null') {
                        return;
                    }

                    try {
                        // Decode HTML entities (fix for htmlspecialchars encoding)
                        if (tooltipData) {
                            const textarea = document.createElement('textarea');
                            textarea.innerHTML = tooltipData;
                            tooltipData = textarea.value;
                        }
                        const data = JSON.parse(tooltipData);
                        if (!data || data.length === 0) {
                            return;
                        }

                        const $cell = $(this);
                        const cellOffset = $cell.offset();
                        const cellWidth = $cell.outerWidth();
                        const cellHeight = $cell.outerHeight();

                        let tooltipHtml = '<div class="overtime-tooltip show">';
                        tooltipHtml += '<div class="tooltip-header">';
                        tooltipHtml += '<div class="tooltip-date">' + formatDate($cell.attr('data-date')) + '</div>';
                        tooltipHtml += '</div>';
                        tooltipHtml += '<div class="tooltip-info">';

                        data.forEach(function(ot, index) {
                            if (index > 0) tooltipHtml += '<hr style="margin: 8px 0; border-color: #444;">';
                            tooltipHtml += '<div><span class="info-label">Entry ' + (index + 1) + ':</span></div>';
                            tooltipHtml += '<div><span class="info-label">Check In:</span><span class="info-value">' + ot.start_time + '</span></div>';
                            tooltipHtml += '<div><span class="info-label">Check Out:</span><span class="info-value">' + ot.end_time + '</span></div>';
                            tooltipHtml += '<div><span class="info-label">Total:</span><span class="info-value overtime-value">' + ot.total_time + '</span></div>';

                            // Status with color
                            let statusClass = '';
                            let statusText = ot.status.charAt(0).toUpperCase() + ot.status.slice(1);
                            if(ot.status === 'pending') {
                                statusClass = 'status-pending';
                            } else if(ot.status === 'rejected') {
                                statusClass = 'status-rejected';
                            } else if(ot.status === 'approved') {
                                statusClass = 'status-approved';
                            }
                            tooltipHtml += '<div><span class="info-label">Status:</span><span class="info-value ' + statusClass + '">' + statusText + '</span></div>';

                            if (ot.shift) {
                                tooltipHtml += '<div><span class="info-label">Shift:</span><span class="info-value">' + ot.shift + '</span></div>';
                            }
                        });

                        tooltipHtml += '</div>';
                        tooltipHtml += '</div>';

                        const $tooltip = $(tooltipHtml);
                        $('body').append($tooltip);

                        const tooltipWidth = $tooltip.outerWidth();
                        const tooltipHeight = $tooltip.outerHeight();

                        const scrollTop = $(window).scrollTop();
                        const scrollLeft = $(window).scrollLeft();
                        const cellTop = cellOffset.top - scrollTop;
                        const cellLeft = cellOffset.left - scrollLeft;

                        const left = cellLeft + (cellWidth / 2) - (tooltipWidth / 2);
                        const top = cellTop - tooltipHeight - 12;

                        const windowWidth = $(window).width();
                        const windowHeight = $(window).height();
                        let finalLeft = left;
                        let finalTop = top;

                        if (finalLeft < 10) {
                            finalLeft = 10;
                        } else if (finalLeft + tooltipWidth > windowWidth - 10) {
                            finalLeft = windowWidth - tooltipWidth - 10;
                        }

                        let arrowClass = '';
                        if (finalTop < 10) {
                            finalTop = cellTop + cellHeight + 12;
                            arrowClass = 'arrow-top';
                        }

                        $tooltip.addClass(arrowClass).css({
                            left: finalLeft + 'px',
                            top: finalTop + 'px',
                            display: 'block'
                        });
                    } catch (e) {
                        console.error('Error parsing tooltip data:', e);
                    }
                },
                mouseleave: function() {
                    $('.overtime-tooltip').remove();
                }
            });
        }

        // Initialize click handlers for editing
        function initOvertimeClickHandlers() {
            $('.overtime-cell').off('click').on('click', function() {
                let date = $(this).attr('data-date');
                let empId = $(this).attr('data-emp-id');
                let tooltipData = $(this).attr('data-tooltip');

                if (!tooltipData || tooltipData === '' || tooltipData === 'null') {
                    // No overtime data, allow adding new
                    openOvertimeModal(date, empId, []);
                } else {
                    try {
                        // Decode HTML entities (fix for htmlspecialchars encoding)
                        if (tooltipData) {
                            const textarea = document.createElement('textarea');
                            textarea.innerHTML = tooltipData;
                            tooltipData = textarea.value;
                        }
                        const data = JSON.parse(tooltipData);
                        openOvertimeModal(date, empId, data);
                    } catch (e) {
                        console.error('Error parsing overtime data:', e);
                    }
                }
            });
        }

        // Open overtime edit modal
        function openOvertimeModal(date, empId, overtimeEntries) {
            $('#overtimeModalDate').val(date);
            $('#overtimeModalEmpId').val(empId);
            $('#overtimeEntriesContainer').empty();

            if (!overtimeEntries || overtimeEntries.length === 0) {
                // No overtime data, allow adding new
                addOvertimeEntry(null, 1);
            } else {
                // Loop through all overtime entries and display them
                overtimeEntries.forEach(function(entry, index) {
                    addOvertimeEntry(entry, index + 1);
                });
            }

            // Show modal - try both Bootstrap 4 and 5 syntax
            if (typeof bootstrap !== 'undefined') {
                // Bootstrap 5
                var modal = new bootstrap.Modal(document.getElementById('editOvertimeModal'));
                modal.show();
            } else {
                // Bootstrap 4
                $('#editOvertimeModal').modal('show');
            }
        }

        // Add overtime entry row
        function addOvertimeEntry(entry = null, entryNumber = null) {
            // Get current entry count if not provided
            if (entryNumber === null) {
                entryNumber = $('#overtimeEntriesContainer .overtime-entry-row').length + 1;
            }

            let entryHtml = '<div class="overtime-entry-row mb-3 p-3 border rounded">';
            if (entry && entry.id) {
                entryHtml += '<input type="hidden" class="overtime-entry-id" value="' + entry.id + '">';
            }
            entryHtml += '<div class="d-flex justify-content-between align-items-center mb-2">';
            entryHtml += '<h6 class="mb-0">Entry ' + entryNumber + '</h6>';
            entryHtml += '<button type="button" class="btn btn-sm btn-danger remove-overtime-entry"><i class="fa fa-times"></i> Remove</button>';
            entryHtml += '</div>';
            entryHtml += '<div class="row g-3">';
            entryHtml += '<div class="col-md-4">';
            entryHtml += '<label class="form-label">Check In Time</label>';
            entryHtml += '<input type="text" class="form-control overtime-start-time" value="' + (entry ? entry.start_time : '') + '" placeholder="HH:MM">';
            entryHtml += '</div>';
            entryHtml += '<div class="col-md-4">';
            entryHtml += '<label class="form-label">Check Out Time</label>';
            entryHtml += '<input type="text" class="form-control overtime-end-time" value="' + (entry ? entry.end_time : '') + '" placeholder="HH:MM">';
            entryHtml += '</div>';
            entryHtml += '<div class="col-md-4">';
            entryHtml += '<label class="form-label">Status</label>';
            entryHtml += '<select class="form-select overtime-status">';
            entryHtml += '<option value="pending"' + (entry && entry.status === 'pending' ? ' selected' : '') + '>Pending</option>';
            entryHtml += '<option value="approved"' + (entry && entry.status === 'approved' ? ' selected' : '') + '>Approved</option>';
            entryHtml += '<option value="rejected"' + (entry && entry.status === 'rejected' ? ' selected' : '') + '>Rejected</option>';
            entryHtml += '</select>';
            entryHtml += '</div>';
            entryHtml += '</div>';
            entryHtml += '</div>';

            $('#overtimeEntriesContainer').append(entryHtml);

            // Initialize time pickers for the newly added entry
            let $newRow = $('#overtimeEntriesContainer .overtime-entry-row').last();
            $newRow.find('.overtime-start-time, .overtime-end-time').each(function() {
                if (!$(this).data('flatpickr')) {
                    flatpickr(this, {
                        enableTime: true,
                        noCalendar: true,
                        dateFormat: "H:i",
                        time_24hr: true,
                        minuteIncrement: 1
                    });
                }
            });
        }

        // Remove overtime entry
        $(document).on('click', '.remove-overtime-entry', function() {
            $(this).closest('.overtime-entry-row').remove();
            // Renumber remaining entries
            $('#overtimeEntriesContainer .overtime-entry-row').each(function(index) {
                $(this).find('h6').text('Entry ' + (index + 1));
            });
        });

        // Add new overtime entry
        $(document).on('click', '#addOvertimeEntry', function() {
            let entryCount = $('#overtimeEntriesContainer .overtime-entry-row').length;
            addOvertimeEntry(null, entryCount + 1);
        });

        // Save overtime
        $('#saveOvertimeForm').on('submit', function(e) {
            e.preventDefault();

            let date = $('#overtimeModalDate').val();
            let empId = $('#overtimeModalEmpId').val();
            let entries = [];

            $('.overtime-entry-row').each(function() {
                let entryId = $(this).find('.overtime-entry-id').val();
                let startTime = $(this).find('.overtime-start-time').val();
                let endTime = $(this).find('.overtime-end-time').val();
                let status = $(this).find('.overtime-status').val();

                if (startTime && endTime) {
                    entries.push({
                        id: entryId || null,
                        start_time: startTime,
                        end_time: endTime,
                        status: status
                    });
                }
            });

            if (entries.length === 0) {
                toastr.error('Please add at least one overtime entry.', "Error", {
                    positionClass: 'toast-bottom-right'
                });
                return;
            }

            $.ajax({
                url: "<?php echo e(route('resort.timeandattendance.StoreOverTime')); ?>",
                type: "POST",
                data: {
                    "_token": "<?php echo e(csrf_token()); ?>",
                    "date": date,
                    "Emp_id": empId,
                    "entries": entries
                },
                success: function(response) {
                    if (response.success) {
                        // Hide modal - try both Bootstrap 4 and 5 syntax
                        if (typeof bootstrap !== 'undefined') {
                            var modalElement = document.getElementById('editOvertimeModal');
                            var modal = bootstrap.Modal.getInstance(modalElement);
                            if (modal) {
                                modal.hide();
                            }
                        } else {
                            $('#editOvertimeModal').modal('hide');
                        }
                        toastr.success(response.message, "Success", {
                            positionClass: 'toast-bottom-right'
                        });
                        setTimeout(function() {
                            window.location.reload();
                        }, 1000);
                    } else {
                        toastr.error(response.message, "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                },
                error: function(response) {
                    if (response.status === 422) {
                        var errors = response.responseJSON.errors;
                        var errs = '';
                        $.each(errors, function (field, messages) {
                            $.each(messages, function (index, message) {
                                errs += message + '<br>';
                            });
                        });
                        toastr.error(errs, "Validation Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    } else {
                        toastr.error("An unexpected error occurred.", "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                }
            });
        });

        // Format date for tooltip
        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
        }

        // Initialize on page load
        $(document).ready(function() {
            initOvertimeTooltips();
            initOvertimeClickHandlers();
        });
    </script>
<?php $__env->stopSection(); ?>


<?php echo $__env->make('resorts.layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\wamp64\www\Wisdom-Ai\HRVMS-Wisdom-AI\resources\views/resorts/timeandattendance/dutyroster/Overtime.blade.php ENDPATH**/ ?>