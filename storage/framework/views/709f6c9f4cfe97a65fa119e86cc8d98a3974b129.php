<?php $__env->startSection('page_tab_title' ,$page_title); ?>

    <?php if($message = Session::get('success')): ?>
        <div class="alert alert-success">
            <p><?php echo e($message); ?></p>
        </div>
    <?php endif; ?>

    <?php $__env->startSection('content'); ?>

    <div class="body-wrapper pb-5">
        <div class="container-fluid">
            <div class="page-hedding page-appHedding">
                <div class="row justify-content-between g-md-2 g-1">
                    <div class="col-auto">
                        <div class="page-title">
                            <span>Time And Attendance</span>
                            <h1><?php echo e($page_title); ?></h1>
                        </div>
                    </div>
                </div>
            </div>


            <div class="card">
                <div class="card-header">
                    <div class="row g-md-3 g-2 align-items-center">
                        <div class="col-xl-3 col-lg-5 col-md-7 col-sm-8 ">
                            <div class="input-group">
                                <input type="search" class="form-control search" placeholder="Search" />
                                <i class="fa-solid fa-search"></i>
                            </div>
                        </div>
                        <div class="col-xl-2 col-md-5 col-sm-4 col-6">
                            <select class="form-select Department" id="department" name="department">
                                <option ></option>
                                <?php $__currentLoopData = $ResortDepartment; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($r->id); ?>"><?php echo e($r->name); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>
                        <div class="col-xl-2 col-md-3 col-sm-4 col-6">
                            <select class="form-select Position" name="position" id="position">

                            </select>
                        </div>
                        <div class="col-xl-2 col-md-3 col-sm-4 col-6">
                            <input type="text"  class="form-control  datepicker" id="RegisterCreateDatePickerFilter" placeholder="Select Date">

                        </div>

                        <div class="col-xl-2 col-md-3 col-sm-4 col-6">
                            <button class="btn btn-themeBlue btn-sm" id="clearFilter">Clear Filter</button>
                        </div>
                        <div class="col-auto ms-auto">
                            <div class="view-toggle-group">
                                <a href="javascript:void(0)" class="btn btn-icon-toggle btn-normal active" title="Normal View">
                                    <i class="fa-regular fa-calendar"></i>
                                </a>
                                <a href="javascript:void(0)" class="btn btn-icon-toggle btn-detailed" title="Detailed List View">
                                    <i class="fa-regular fa-list"></i>
                                </a>
                            </div>
                        </div>

                    </div>
                </div>
                <div class="filtertaleData">
                    <!-- Normal View - Calendar Grid -->
                    <div class="view-normal-container">
                        <div class="attendance-calendar-header mb-3">
                            <div class="row align-items-center">
                                
                                <div class="col-auto ms-auto">
                                    <div class="attendance-legend">
                                        <span class="legend-item"><span class="legend-color bg-present"></span>Present</span>
                                        <span class="legend-item"><span class="legend-color bg-absent"></span>Absent</span>
                                        <span class="legend-item"><span class="legend-color bg-late"></span>Late</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="attendance-calendar-grid mb-4">
                            <div class="table-responsive">
                                <table class="table table-bordered attendance-grid-table mb-0">
                                    <thead>
                                        <tr>
                                            <th class="employee-col">EMPLOYEE</th>
                                            <?php $__currentLoopData = $monthwiseheaders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $h): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <?php
                                                    $currentDate = isset($h['newdate']) ? \Carbon\Carbon::createFromFormat('d-m-Y', $h['newdate'])->format('Y-m-d') : (date('Y-m') . '-' . str_pad($h['day'], 2, '0', STR_PAD_LEFT));
                                                    $isPublicHoliday = isset($publicHolidays) && in_array($currentDate, $publicHolidays);
                                                ?>
                                                <th class="date-col <?php echo e($isPublicHoliday ? 'public-holiday-header' : ''); ?>">
                                                    <div><?php echo e($h['dayname']); ?></div>
                                                    <div><?php echo e($h['day']); ?></div>
                                                </th>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                            <?php if($LeaveCategory->isNotEmpty()): ?>
                                                <?php $__currentLoopData = $LeaveCategory; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $l): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                    <th class="leave-stat-col">
                                                        <span class="leave-stat-badge" style="background-color: <?php echo e($l->color); ?>">
                                                            <?php echo e(substr($l->leave_type, 0, 1)); ?>

                                                        </span>
                                                    </th>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                            <?php endif; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if($attandanceregister->isNotEmpty()): ?>
                                            <?php $__currentLoopData = $attandanceregister; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $a): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <?php
                                                    $RosterInternalDataMonth = Common::GetAttandanceRegister($resort_id, $a->duty_roster_id, $a->emp_id, $WeekstartDate, $WeekendDate,$startOfMonth,$endOfMonth, "Monthwise");
                                                ?>
                                                <tr>
                                                    <td class="employee-col">
                                                        <div class="employee-info">
                                                            <strong><?php echo e($a->EmployeeName); ?></strong>
                                                            <small class="text-muted d-block"><?php echo e($a->Position); ?></small>
                                                        </div>
                                                    </td>
                                                    <?php $__currentLoopData = $monthwiseheaders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $h): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                        <?php
                                                            $date = isset($h['newdate']) ? \Carbon\Carbon::createFromFormat('d-m-Y', $h['newdate'])->format('Y-m-d') : (date('Y-m') . '-' . str_pad($h['day'], 2, '0', STR_PAD_LEFT));
                                                            $isPublicHoliday = isset($publicHolidays) && in_array($date, $publicHolidays);
                                                            $shiftData = $RosterInternalDataMonth->firstWhere('date', $date);

                                                            // Get overtime from employee_overtimes table
                                                            $overtimeForDate = null;
                                                            if ($overtimeData->has($a->emp_id)) {
                                                                $empOvertime = $overtimeData->get($a->emp_id);
                                                                if ($empOvertime->has($date)) {
                                                                    $overtimeForDate = $empOvertime->get($date);
                                                                }
                                                            }
                                                            $overtimeValue = $overtimeForDate ? $overtimeForDate->total_time : '0:0';

                                                            $cellClass = 'attendance-cell';
                                                            $cellStatus = '';
                                                            $cellLabel = '';
                                                            $tooltipData = '';
                                                            $cellStyle = '';

                                                            if ($shiftData) {
                                                                if($shiftData->Status == "Present") {
                                                                    $cellClass .= ' bg-present';
                                                                    $cellLabel = 'P';
                                                                    $cellStatus = 'PRESENT';
                                                                    $tooltipData = json_encode([
                                                                        'date' => $date,
                                                                        'status' => 'PRESENT',
                                                                        'punchIn' => $shiftData->CheckingTime ?? '--:--',
                                                                        'punchOut' => $shiftData->CheckingOutTime ?? '--:--',
                                                                        'overtime' => $overtimeValue
                                                                    ]);
                                                                } elseif($shiftData->Status == "Absent") {
                                                                    $cellClass .= ' bg-absent';
                                                                    $cellLabel = 'A';
                                                                    $cellStatus = 'ABSENT';
                                                                    $tooltipData = json_encode([
                                                                        'date' => $date,
                                                                        'status' => 'ABSENT'
                                                                    ]);
                                                                } elseif($shiftData->LeaveFirstName != null) {
                                                                    $leaveColor = $shiftData->LeaveColor ?? '#FFC107';
                                                                    $cellClass .= ' bg-leave';
                                                                    $cellLabel = substr($shiftData->LeaveFirstName, 0, 1);
                                                                    $cellStatus = strtoupper($shiftData->LeaveFirstName);
                                                                    $cellStyle = 'background-color: ' . $leaveColor . ' !important;';
                                                                    $tooltipData = json_encode([
                                                                        'date' => $date,
                                                                        'status' => $shiftData->LeaveFirstName,
                                                                        'color' => $leaveColor
                                                                    ]);
                                                                } elseif($shiftData->Status == "DayOff") {
                                                                    $cellClass .= ' bg-dayoff';
                                                                    $cellLabel = '-';
                                                                    $cellStatus = 'DAY OFF';
                                                                    $tooltipData = json_encode([
                                                                        'date' => $date,
                                                                        'status' => 'DAY OFF'
                                                                    ]);
                                                                } else {
                                                                    $cellClass .= ' bg-empty';
                                                                    $cellLabel = '-';
                                                                }
                                                            } else {
                                                                $cellClass .= ' bg-empty';
                                                                $cellLabel = '-';
                                                            }
                                                        ?>
                                                        <td class="<?php echo e($cellClass); ?> <?php echo e($isPublicHoliday ? 'public-holiday-cell' : ''); ?>"
                                                            data-tooltip="<?php echo e(htmlspecialchars($tooltipData)); ?>"
                                                            data-date="<?php echo e($date); ?>"
                                                            data-employee="<?php echo e($a->EmployeeName); ?>"
                                                            data-status="<?php echo e($cellStatus); ?>"
                                                            <?php if($cellStyle): ?> style="<?php echo e($cellStyle); ?>" <?php endif; ?>>
                                                            <?php echo e($cellLabel); ?>

                                                        </td>
                                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                    <?php if($LeaveCategory->isNotEmpty()): ?>
                                                        <?php $__currentLoopData = $LeaveCategory; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $l): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                            <?php
                                                                $leaveTypeCount = 0;
                                                                foreach ($RosterInternalDataMonth as $shiftData) {
                                                                    if (isset($shiftData->LeaveData) && is_array($shiftData->LeaveData)) {
                                                                        foreach ($shiftData->LeaveData as $leaveData) {
                                                                            if (isset($leaveData['leave_cat_id']) && $leaveData['leave_cat_id'] == $l->id) {
                                                                                $leaveTypeCount++;
                                                                            }
                                                                        }
                                                                    }
                                                                }
                                                            ?>
                                                            <td class="leave-stat-cell">
                                                                <span class="leave-stat-badge" style="background-color: <?php echo e($l->color); ?>">
                                                                    <?php echo e($leaveTypeCount); ?>

                                                                </span>
                                                            </td>
                                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                    <?php endif; ?>
                                                </tr>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="pagination-custom mt-4">
                            <nav aria-label="Page navigation example">
                                <?php echo $attandanceregister->appends(['view' => request('view', 'normal')])->links('pagination::bootstrap-4'); ?>

                            </nav>
                        </div>
                    </div>

                    <!-- Detailed List View -->
                    <div class="view-detailed-container d-none">
                        <div class="employee-list-view">
                            <?php if($attandanceregister->isNotEmpty()): ?>
                                <?php $__currentLoopData = $attandanceregister; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $a): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <?php
                                        $RosterInternalDataMonth = Common::GetAttandanceRegister($resort_id, $a->duty_roster_id, $a->emp_id, $WeekstartDate, $WeekendDate,$startOfMonth,$endOfMonth, "Monthwise");

                                        // Calculate summary statistics
                                        $presentCount = 0;
                                        $absentCount = 0;
                                        $leaveCount = 0;
                                        $totalOtHours = 0;

                                        // Calculate total OT hours from employee_overtimes table
                                        if ($overtimeData->has($a->emp_id)) {
                                            $empOvertime = $overtimeData->get($a->emp_id);
                                            foreach ($empOvertime as $otEntry) {
                                                list($Othours, $Otminutes) = explode(':', $otEntry->total_time ?? '0:0');
                                                $totalOtHours += (int)$Othours + ((int)$Otminutes / 60);
                                            }
                                        }

                                        foreach ($RosterInternalDataMonth as $shiftData) {
                                            if ($shiftData->Status == "Present") {
                                                $presentCount++;
                                            } elseif ($shiftData->Status == "Absent") {
                                                $absentCount++;
                                            } elseif (isset($shiftData->LeaveData) && is_array($shiftData->LeaveData) && !empty($shiftData->LeaveData)) {
                                                $leaveCount++;
                                            }
                                        }

                                        // Calculate leave type counts
                                        $employeeLeaveStats = [];
                                        foreach ($LeaveCategory as $l) {
                                            $leaveTypeCount = 0;
                                            foreach ($RosterInternalDataMonth as $shiftData) {
                                                if (isset($shiftData->LeaveData) && is_array($shiftData->LeaveData)) {
                                                    foreach ($shiftData->LeaveData as $leaveData) {
                                                        if (isset($leaveData['leave_cat_id']) && $leaveData['leave_cat_id'] == $l->id) {
                                                            $leaveTypeCount++;
                                                        }
                                                    }
                                                }
                                            }
                                            $employeeLeaveStats[$l->id] = [
                                                'name' => $l->leave_type,
                                                'color' => $l->color,
                                                'count' => $leaveTypeCount
                                            ];
                                        }
                                    ?>
                                    <div class="employee-card mb-3" data-employee-id="<?php echo e($a->emp_id); ?>">
                                        <div class="employee-card-header" onclick="toggleEmployeeDetails(<?php echo e($a->emp_id); ?>)">
                                            <div class="row align-items-center">
                                                <div class="col-auto">
                                                    <div class="employee-avatar">
                                                        <img src="<?php echo e($a->profileImg); ?>" alt="<?php echo e($a->EmployeeName); ?>">
                                                        <span class="status-dot"></span>
                                                    </div>
                                                </div>
                                                <div class="col">
                                                    <h6 class="mb-0"><?php echo e($a->EmployeeName); ?></h6>
                                                    <small class="text-muted">
                                                        <i class="fa-regular fa-briefcase me-1"></i><?php echo e($a->Position); ?>

                                                    </small>
                                                </div>
                                                <div class="col-auto">
                                                    <div class="employee-stats-summary">
                                                        <span class="stat-badge stat-present">
                                                            <i class="fa-regular fa-user-check me-1"></i><?php echo e($presentCount); ?> Present
                                                        </span>
                                                        <span class="stat-badge stat-absent">
                                                            <i class="fa-regular fa-user-xmark me-1"></i><?php echo e($absentCount); ?> Absent
                                                        </span>
                                                        <span class="stat-badge stat-leave">
                                                            <i class="fa-regular fa-calendar me-1"></i><?php echo e($leaveCount); ?> Leave
                                                        </span>
                                                        <span class="stat-badge stat-ot">
                                                            <i class="fa-regular fa-clock me-1"></i><?php echo e(round($totalOtHours)); ?>h OT Hours
                                                        </span>
                                                    </div>
                                                </div>
                                                <div class="col-auto">
                                                    <i class="fa-regular fa-chevron-down toggle-icon"></i>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="employee-card-details d-none" id="employee-details-<?php echo e($a->emp_id); ?>">
                                            <div class="attendance-timeline">
                                                <div class="timeline-scroll">
                                                    <?php $__currentLoopData = $monthwiseheaders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $h): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                        <?php
                                                            $date = isset($h['newdate']) ? \Carbon\Carbon::createFromFormat('d-m-Y', $h['newdate'])->format('Y-m-d') : (date('Y-m') . '-' . str_pad($h['day'], 2, '0', STR_PAD_LEFT));
                                                            $isPublicHoliday = isset($publicHolidays) && in_array($date, $publicHolidays);
                                                            $shiftData = $RosterInternalDataMonth->firstWhere('date', $date);

                                                            // Get overtime from employee_overtimes table
                                                            $overtimeForDate = null;
                                                            if ($overtimeData->has($a->emp_id)) {
                                                                $empOvertime = $overtimeData->get($a->emp_id);
                                                                if ($empOvertime->has($date)) {
                                                                    $overtimeForDate = $empOvertime->get($date);
                                                                }
                                                            }
                                                        ?>
                                                        <div class="timeline-day <?php echo e($isPublicHoliday ? 'public-holiday-cell' : ''); ?>">
                                                            <?php if($shiftData && $shiftData->Status == "Present"): ?>
                                                                <div class="day-label d-flex justify-content-between">
                                                                    <?php echo e($h['dayname']); ?> <?php echo e($h['day']); ?>

                                                                    <span class="workday-dot"></span>
                                                                </div>
                                                                <div class="day-content">
                                                                    <div class="time-entry">
                                                                        <span class="time-label">In</span>
                                                                        <span class="time-value"><?php echo e($shiftData->CheckingTime ?? '--:--'); ?></span>
                                                                    </div>
                                                                    <div class="time-entry">
                                                                        <span class="time-label">Out</span>
                                                                        <span class="time-value"><?php echo e($shiftData->CheckingOutTime ?? '--:--'); ?></span>
                                                                    </div>
                                                                    <?php if($overtimeForDate && $overtimeForDate->total_time && $overtimeForDate->total_time != "0:0" && $overtimeForDate->total_time != "00:00"): ?>
                                                                        <div class="ot-badge">
                                                                            <i class="fa-regular fa-clock me-1"></i>+<?php echo e($overtimeForDate->total_time); ?> OT
                                                                        </div>
                                                                    <?php endif; ?>
                                                                </div>
                                                            <?php elseif($shiftData && $shiftData->Status == "Absent"): ?>
                                                                <div class="day-label"><?php echo e($h['dayname']); ?> <?php echo e($h['day']); ?></div>
                                                                <div class="day-content absent-content">Absent</div>
                                                            <?php elseif($shiftData && $shiftData->LeaveFirstName != null): ?>
                                                                <div class="day-label"><?php echo e($h['dayname']); ?> <?php echo e($h['day']); ?></div>
                                                                <div class="day-content leave-content" style="background-color: <?php echo e($shiftData->LeaveColor ?? '#FFC107'); ?>20; border-left-color: <?php echo e($shiftData->LeaveColor ?? '#FFC107'); ?>">
                                                                    <?php echo e($shiftData->LeaveFirstName); ?>

                                                                </div>
                                                            <?php else: ?>
                                                                <div class="day-label"><?php echo e($h['dayname']); ?> <?php echo e($h['day']); ?></div>
                                                                <div class="day-content empty-content">-</div>
                                                            <?php endif; ?>
                                                        </div>
                                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            <?php endif; ?>

                            <div class="pagination-custom mt-4">
                                <nav aria-label="Page navigation example">
                                    <?php echo $attandanceregister->appends(['view' => request('view', 'detailed')])->links('pagination::bootstrap-4'); ?>

                                </nav>
                            </div>
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
    <div class="modal fade" id="CheckOutModel-modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="staticBackdropLabel">CheckOut Missing</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body ">
                    <div class="row">
                    <form id="checkoutForm">
                        <div class="table-responsive mb-3">
                            <div class="col-12 mt-3">

                                    <?php echo csrf_field(); ?>
                                <table class=" table-timeAttenRespond">
                                    <tbody>
                                        <tr>
                                            <th>Shift Name:</th>
                                            <td><p id="todoshiftname"></p></td>
                                        </tr>
                                        <tr>
                                            <th>Shift Starting Time:</th>
                                            <td><p id="todoshiftstime"></p></td>
                                        </tr>
                                        <tr>
                                            <th>Shift Ending Time:</th>
                                            <td><p id="todoshiftetime"></p></td>
                                        </tr>
                                        <tr>
                                            <th>Assigned Overtime:</th>
                                            <td><p id="todoassignedot"></p></td>
                                        </tr>

                                        <tr>
                                            <th>Checkout Time:</th>
                                            <td>
                                                <input type="time" class="form-control CheckoutTime" id="CheckoutTime" name="CheckoutTime" placeholder="Checkout Time">

                                            </td>


                                            <input type="hidden" id="attendance_id">
                                            <input type="hidden" id="todoshiftstimehidden">
                                            <input type="hidden" id="todoshiftetimehidden">
                                        </tr>

                                    </tbody>
                                </table>
                            </div>
                            
                        </div>
                        <div class="row g-2 justify-content-center mb-3">
                            <div class="col-auto">
                                <button type="submit" class="btn btn-themeBlue btn-sm todoListApprove" data-button="approve"><i  class="fa-solid fa-check me-2"></i>Approved</button>
                            </div>
                            <div class="col-auto">
                                <button type="submit" class="btn btn-danger btn-sm todoListReject"  data-button="reject"><i class="fa-solid fa-xmark me-2"></i>Reject</button>
                            </div>
                        </div>
                     </form>
                 </div>
            </div>
        </div>
    </div>
    <?php $__env->stopSection(); ?>
    <?php $__env->startSection('import-css'); ?>
    <style>
        .view-toggle-group {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            background: #f5f5f5;
            padding: 4px;
            border-radius: 6px;
        }

        .btn-icon-toggle {
            width: 36px;
            height: 36px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: none;
            background: transparent;
            color: #6c757d;
            border-radius: 4px;
            transition: all 0.3s ease;
            padding: 0;
            text-decoration: none;
        }

        .btn-icon-toggle:hover {
            background: #e9ecef;
            color: #495057;
        }

        .btn-icon-toggle.active {
            background: #014653;
            color: #ffffff;
            box-shadow: 0 2px 4px rgba(0, 123, 255, 0.3);
        }

        .btn-icon-toggle i {
            font-size: 16px;
        }

        /* Normal View - Calendar Grid Styles */
        .attendance-calendar-header {
            padding: 15px;
            background: #fff;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .attendance-legend {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            color: #666;
        }

        .legend-color {
            width: 16px;
            height: 16px;
            border-radius: 3px;
            display: inline-block;
        }

        .bg-present {
            background-color: #3EB95F !important;
        }

        .bg-absent {
            background-color: #FF4B4B !important;
        }

        .bg-late {
            background-color: #FFC107 !important;
        }

        .bg-leave {
            background-color: #53CAFF !important;
        }

        .bg-dayoff {
            background-color: #E0E0E0 !important;
        }

        .bg-empty {
            background-color: #F5F5F5 !important;
        }

        .attendance-grid-table {
            font-size: 13px;
        }

        .attendance-grid-table th {
            background: #F5F8F8;
            font-weight: 600;
            text-align: center;
            padding: 12px 8px;
            border: 1px solid #E0E0E0;
        }

        .attendance-grid-table .employee-col {
            position: sticky;
            left: 0;
            background: #fff;
            z-index: 10;
            min-width: 200px;
            text-align: left;
            padding: 12px 15px;
        }

        .attendance-grid-table .date-col {
            min-width: 50px;
            text-align: center;
            padding: 8px 4px;
        }

        .attendance-grid-table .date-col div:first-child {
            font-size: 11px;
            color: #999;
            font-weight: normal;
        }

        .attendance-grid-table .date-col div:last-child {
            font-size: 14px;
            font-weight: 600;
            color: #333;
        }

        .attendance-grid-table .leave-stat-col {
            min-width: 50px;
            text-align: center;
            padding: 8px 4px;
            background: #F5F8F8;
        }

        .leave-stat-cell {
            text-align: center;
            padding: 8px 4px;
            border: 1px solid #E0E0E0;
            background: #fff;
        }

        .leave-stat-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 32px;
            height: 32px;
            border-radius: 16px;
            color: #fff;
            font-weight: 600;
            font-size: 13px;
            padding: 0 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.2);
        }

        .attendance-cell {
            text-align: center;
            padding: 12px 4px;
            font-weight: 600;
            font-size: 14px;
            color: #333;
            cursor: pointer;
            transition: all 0.2s ease;
            position: relative;
            border: 1px solid #E0E0E0;
        }

        .attendance-cell:hover {
            transform: scale(1.05);
            z-index: 5;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        }

        .employee-info {
            line-height: 1.4;
        }

        .employee-info strong {
            display: block;
            font-size: 14px;
        }

        .employee-info small {
            font-size: 12px;
        }

        /* Tooltip Styles */
        .attendance-tooltip {
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

        .attendance-tooltip.show {
            display: block !important;
        }

        .attendance-tooltip::after {
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

        .attendance-tooltip.arrow-top::after {
            bottom: auto;
            top: -8px;
            border-top: none;
            border-bottom: 8px solid #2C2C2C;
        }

        .attendance-tooltip .tooltip-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
        }

        .attendance-tooltip .tooltip-date {
            font-weight: 600;
            font-size: 13px;
            color: #fff;
        }

        .attendance-tooltip .tooltip-status {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .attendance-tooltip .tooltip-status.present {
            background: #3EB95F;
            color: #fff;
        }

        .attendance-tooltip .tooltip-status.absent {
            background: #FF4B4B;
            color: #fff;
        }

        .attendance-tooltip .tooltip-status.leave {
            background: #53CAFF;
            color: #fff;
        }

        .attendance-tooltip .tooltip-status.day-off {
            background: #9E9E9E;
            color: #fff;
        }

        .attendance-tooltip .tooltip-info {
            margin-top: 0;
            line-height: 1.8;
        }

        .attendance-tooltip .tooltip-info div {
            margin-bottom: 4px;
            color: #fff;
            font-size: 12px;
        }

        .attendance-tooltip .tooltip-info .info-label {
            color: #ccc;
            margin-right: 8px;
        }

        .attendance-tooltip .tooltip-info .info-value {
            color: #fff;
            font-weight: 500;
        }

        .attendance-tooltip .tooltip-info .overtime-value {
            color: #FFA726;
            font-weight: 600;
        }

        /* Statistics Section */
        .attendance-statistics {
            background: #F5F8F8;
            padding: 20px;
            border-radius: 8px;
        }

        .stat-card {
            background: #fff;
            padding: 15px 20px;
            border-radius: 6px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .stat-label {
            font-size: 12px;
            color: #666;
            margin-bottom: 5px;
        }

        .stat-value {
            font-size: 24px;
            font-weight: 700;
        }

        /* Detailed List View Styles */
        .employee-card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.08);
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .employee-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.12);
        }

        .employee-card-header {
            padding: 20px;
            cursor: pointer;
            background: #fff;
        }

        .employee-card-header:hover {
            background: #F9F9F9;
        }

        .employee-avatar {
            position: relative;
            width: 50px;
            height: 50px;
        }

        .employee-avatar img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }

        .status-dot {
            position: absolute;
            bottom: 2px;
            right: 2px;
            width: 12px;
            height: 12px;
            background: #3EB95F;
            border: 2px solid #fff;
            border-radius: 50%;
        }

        .employee-stats-summary {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .stat-badge {
            display: inline-flex;
            align-items: center;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .stat-badge.stat-present {
            background: #E8F5E9;
            color: #2E7D32;
        }

        .stat-badge.stat-absent {
            background: #FFEBEE;
            color: #C62828;
        }

        .stat-badge.stat-leave {
            background: #E3F2FD;
            color: #1565C0;
        }

        .stat-badge.stat-ot {
            background: #FFF3E0;
            color: #E65100;
        }

        .toggle-icon {
            transition: transform 0.3s ease;
            color: #999;
        }

        .employee-card.active .toggle-icon {
            transform: rotate(180deg);
        }

        .employee-card-details {
            padding: 0 20px 20px;
            background: #F9F9F9;
        }

        .attendance-timeline {
            margin-top: 20px;
        }

        .timeline-scroll {
            display: flex;
            gap: 12px;
            overflow-x: auto;
            padding: 10px 0;
            -webkit-overflow-scrolling: touch;
        }

        .timeline-day {
            min-width: 100px;
            background: #fff;
            border-radius: 6px;
            padding: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .timeline-day.weekend {
            background: #F5F5F5;
        }

        .timeline-day.public-holiday-cell {
            background-color: #ff5a5773 !important;
        }

        .day-label {
            font-size: 11px;
            color: #999;
            margin-bottom: 8px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .workday-dot {
            display: inline-block;
            width: 6px;
            height: 6px;
            background: #3EB95F;
            border-radius: 50%;
            margin-left: 4px;
        }

        .day-content {
            font-size: 12px;
        }

        .time-entry {
            display: flex;
            justify-content: space-between;
            margin-bottom: 6px;
        }

        .time-label {
            color: #999;
            font-size: 11px;
        }

        .time-value {
            font-weight: 600;
            color: #333;
        }

        .ot-badge {
            display: inline-flex;
            align-items: center;
            margin-top: 6px;
            padding: 4px 8px;
            background: #FFF3E0;
            color: #E65100;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }

        .weekend-content {
            text-align: center;
            color: #999;
            font-style: italic;
            padding: 10px 0;
        }

        .absent-content {
            text-align: center;
            color: #C62828;
            font-weight: 600;
            padding: 10px 0;
        }

        .leave-content {
            text-align: center;
            padding: 10px;
            border-radius: 4px;
            font-weight: 600;
            border-left: 3px solid;
        }

        .empty-content {
            text-align: center;
            color: #CCC;
            padding: 10px 0;
        }

        @media (max-width: 768px) {
            .employee-stats-summary {
                flex-direction: column;
                gap: 8px;
            }

            .timeline-day {
                min-width: 80px;
            }
        }
    </style>
    <?php $__env->stopSection(); ?>

    <?php $__env->startSection('import-scripts'); ?>

    <script type="text/javascript">
    $(document).ready(function() {
        $('.datepicker').datepicker({
            format: 'dd/mm/yyyy',
            autoclose: true,      // Close the picker after selection
            todayHighlight: true  // Highlight today's date
        });


            $(".Department").select2({
                placeholder: "Select Department"
            });

            $(".Position").select2({
                placeholder: "select Position"
            });
            // Initialize: Show Normal view by default
            $(".view-normal-container").removeClass("d-none");
            $(".view-detailed-container").addClass("d-none");

            // Initialize tooltips for Normal View
            setTimeout(function() {
                initAttendanceTooltips();
            }, 500);
    });

    // Normal/Detailed View Toggle
    $(".btn-normal").click(function () {
        $(this).addClass("active");
        $(".btn-detailed").removeClass("active");
        $(".view-normal-container").removeClass("d-none");
        $(".view-detailed-container").addClass("d-none");
        initAttendanceTooltips();
    });

    $(".btn-detailed").click(function () {
        $(this).addClass("active");
        $(".btn-normal").removeClass("active");
        $(".view-detailed-container").removeClass("d-none");
        $(".view-normal-container").addClass("d-none");
    });

    // Initialize attendance tooltips for Normal View
    function initAttendanceTooltips() {
        // Remove existing tooltip
        $('.attendance-tooltip').remove();

        const $cells = $('.attendance-cell');

        $cells.off('mouseenter mouseleave').on({
            mouseenter: function(e) {
                let tooltipData = $(this).attr('data-tooltip');
                // Decode HTML entities (fix for htmlspecialchars encoding)
                if (tooltipData) {
                    const textarea = document.createElement('textarea');
                    textarea.innerHTML = tooltipData;
                    tooltipData = textarea.value;
                }
                if (!tooltipData || tooltipData === '' || tooltipData === 'null') {
                    return;
                }

                try {
                    const data = JSON.parse(tooltipData);
                    if (!data || !data.date) {
                        return;
                    }
                    const $cell = $(this);
                    const cellOffset = $cell.offset();
                    const cellWidth = $cell.outerWidth();
                    const cellHeight = $cell.outerHeight();

                    let tooltipHtml = '<div class="attendance-tooltip show">';
                    tooltipHtml += '<div class="tooltip-header">';
                    tooltipHtml += '<div class="tooltip-date">' + formatDate(data.date) + '</div>';

                    if (data.status) {
                        let statusClass = data.status.toLowerCase().replace(/\s+/g, '-');
                        // Handle leave types
                        if (statusClass !== 'present' && statusClass !== 'absent' && statusClass !== 'day-off') {
                            statusClass = 'leave';
                        }
                        let statusStyle = '';
                        // If it's a leave type with custom color, use inline style
                        if (data.color && statusClass === 'leave') {
                            statusStyle = ' style="background-color: ' + data.color + ';"';
                        }
                        tooltipHtml += '<span class="tooltip-status ' + statusClass + '"' + statusStyle + '>' + data.status + '</span>';
                    }
                    tooltipHtml += '</div>';

                    // Always show info section if there's any data
                    tooltipHtml += '<div class="tooltip-info">';
                    if (data.punchIn && data.punchIn !== '--:--' && data.punchIn !== null && data.punchIn !== '') {
                        tooltipHtml += '<div><span class="info-label">Punch In:</span><span class="info-value">' + data.punchIn + '</span></div>';
                    }
                    if (data.punchOut && data.punchOut !== '--:--' && data.punchOut !== null && data.punchOut !== '') {
                        tooltipHtml += '<div><span class="info-label">Punch Out:</span><span class="info-value">' + data.punchOut + '</span></div>';
                    }
                    if (data.overtime && data.overtime !== '0h' && data.overtime !== '0:0' && data.overtime !== '-' && data.overtime !== null && data.overtime !== '' && data.overtime !== '00:00') {
                        // Format overtime properly
                        let overtimeText = data.overtime;
                        if (overtimeText.indexOf(':') !== -1) {
                            const parts = overtimeText.split(':');
                            const hours = parseInt(parts[0]) || 0;
                            const minutes = parseInt(parts[1]) || 0;
                            if (hours > 0) {
                                overtimeText = hours + 'h';
                                if (minutes > 0) {
                                    overtimeText += ' ' + minutes + 'm';
                                }
                            } else if (minutes > 0) {
                                overtimeText = minutes + 'm';
                            } else {
                                overtimeText = null;
                            }
                        }
                        if (overtimeText && overtimeText !== '0h' && overtimeText !== '0m') {
                            tooltipHtml += '<div><span class="info-label">Overtime:</span><span class="info-value overtime-value">' + overtimeText + '</span></div>';
                        }
                    }
                    tooltipHtml += '</div>';

                    tooltipHtml += '</div>';

                    const $tooltip = $(tooltipHtml);
                    $('body').append($tooltip);

                    // Force display before calculating dimensions
                    $tooltip.css('display', 'block');

                    // Position tooltip above the cell, centered
                    const tooltipWidth = $tooltip.outerWidth();
                    const tooltipHeight = $tooltip.outerHeight();

                    // Get scroll positions
                    const scrollTop = $(window).scrollTop();
                    const scrollLeft = $(window).scrollLeft();

                    // Calculate position relative to viewport
                    const cellTop = cellOffset.top - scrollTop;
                    const cellLeft = cellOffset.left - scrollLeft;

                    const left = cellLeft + (cellWidth / 2) - (tooltipWidth / 2);
                    const top = cellTop - tooltipHeight - 12; // 12px gap + 8px arrow

                    // Ensure tooltip doesn't go off screen
                    const windowWidth = $(window).width();
                    const windowHeight = $(window).height();
                    let finalLeft = left;
                    let finalTop = top;

                    if (finalLeft < 10) {
                        finalLeft = 10;
                    } else if (finalLeft + tooltipWidth > windowWidth - 10) {
                        finalLeft = windowWidth - tooltipWidth - 10;
                    }

                    // If tooltip would go above viewport, show it below instead
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
                $('.attendance-tooltip').remove();
            }
        });
    }

    // Format date for tooltip
    function formatDate(dateString) {
        if (!dateString) return '';
        const date = new Date(dateString);
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return year + '-' + month + '-' + day;
    }

    // Toggle employee details in Detailed List View
    function toggleEmployeeDetails(employeeId) {
        const $card = $('[data-employee-id="' + employeeId + '"]');
        const $details = $('#employee-details-' + employeeId);

        $card.toggleClass('active');
        $details.toggleClass('d-none');
    }

    // Make function available globally
    window.toggleEmployeeDetails = toggleEmployeeDetails;

    $(document).on('change', '.Department', function() {
        var deptId = $(this).val();
        $.ajax({
            url: "<?php echo e(route('resort.ta.PositionSections')); ?>",
            type: "post",
            data: {
                deptId: deptId
            },
            success: function(d) {
                // Clear the dropdown and add a placeholder option


                if (d.success == true) {

                    let string = '<option></option>';
                    $.each(d.data.ResortPosition, function(key, value) {
                        string += '<option value="' + value.id + '">' + value
                            .position_title + '</option>';
                    });
                    $(".Position").html(string);

                    let string1 = '<option></option>';
                    $.each(d.data.ResortSection, function(key, value) {
                        string1 += '<option value="' + value.id + '">' + value
                            .name + '</option>';
                    });
                    $(".Section").html(string1);

                }
            },
            error: function(response) {
                toastr.error("Position Not Found", {
                    positionClass: 'toast-bottom-right'
                });
            }
        });
    });
    $(document).on("click", ".LocationHistoryData", function()
    {
        let location1 = $(this).attr('data-location');
        let type =$(this).data('id');
        if (!location1 || location1.trim() === "")
        {
            if(type == undefined)
            {
                type="Not using mobile for login";
            }
            else
            {
                type = "Location";
            }
            toastr.error(type, "Data is not available", {
                positionClass: 'toast-bottom-right'
            });
            return false;
        }
        $("#ModalIframe").attr("src", location1);
        $("#viewMapLocationHistory-modal").modal('show');
    });
    $(document).on("click", ".CheckOutModel", function()
    {
        $("#totalExtraHours").html($(this).attr('data-differenceinhours'));
            $("#todoimage").attr("src",$(this).attr('data-todoimage'));
            $("#todoshiftname").html($(this).attr('data-shiftname'));
            $("#todoshiftstime").html($(this).attr('data-todoshiftstimeShow'));
            $("#todoshiftetime").html($(this).attr('data-todoshiftetimeShow'));
            $("#todoassignedot").html($(this).attr('data-todoassignedot'));
            $("#totalExtraHours").html($(this).attr('data-totalExtraHours'));
            $("#attendance_id").val($(this).attr('data-attendance_id'));

            $("#todoshiftstimehidden").val($(this).attr('data-todoshiftstime'));
            $("#todoshiftetimehidden").val($(this).attr('data-todoshiftetime'));
        $("#CheckOutModel-modal").modal('show');
    });

    $(document).on("click", ".todoListApprove, .todoListReject", function (e) {
        e.preventDefault();

        let flag = $(this).data("button"); // Get the button flag (approve/reject)
        let form = $("#checkoutForm"); // Replace with your form ID
        // Validate the form before proceeding
        form.validate({
            rules: {
                CheckoutTime: {
                    required: true, // CheckoutTime field is required
                },
            },
            messages: {
                CheckoutTime: {
                    required: "Please enter the Checkout Time.", // Custom error message
                },
            },
            errorElement: "div",
            errorClass: "text-danger", // Apply Bootstrap class or any custom class for error
            highlight: function (element) {
                $(element).addClass("is-invalid");
            },
            unhighlight: function (element) {
                $(element).removeClass("is-invalid");
            },
            submitHandler: function () {
                // Trigger confirmation if the form is valid
                confirmations(flag, $("#attendance_id").val());
                $("#eyeRespond-modal").modal("hide");
            },
        });

        // If the form is valid, trigger form submission
        if (form.valid()) {
            form.submit();
        }
    });

    function confirmations(flag, itemId) {
        const action = flag === "approve" ? "approved" : "rejected"; // Determine action based on flag
        alert(action);
        Swal.fire({
            title: `Are you sure you want to ${flag} this OT?`,
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: flag === "approve" ? "#28a745" : "#dc3545", // Green for approve, red for reject
            cancelButtonColor: "#6c757d", // Gray for cancel
            confirmButtonText: `Yes, ${flag} it!`,
            cancelButtonText: "No, cancel",
        }).then((result) => {
            if (result.isConfirmed) {
                // Perform the AJAX request
                $.ajax({
                    url: '<?php echo e(route("resort.timeandattendance.CheckoutTimeMissing")); ?>', // Replace with your backend endpoint
                    type: "POST",
                    data: {
                        _token: "<?php echo e(csrf_token()); ?>",
                        action: flag,
                        AttdanceId: itemId,
                        CheckoutTime: $("#CheckoutTime").val(),
                    },
                    success: function (response) {
                        // Show success message
                        Swal.fire(
                            `${action.charAt(0).toUpperCase() + action.slice(1)}!`,
                            `The OT has been successfully ${action}.`,
                            "success"
                        );
                        window.location.reload(); // Reload the page
                    },
                    error: function (xhr, status, error) {
                        // Show error message
                        Swal.fire(
                            "Error!",
                            "An error occurred while processing the request.",
                            "error"
                        );
                        console.error(error);
                    },
                });
            } else {
                console.log("Action canceled");
            }
        });
    }


    $(document).on('keyup', '.search', function() {
        updateRegisterFilterWiseTable();
    });
    $(document).on('change', '.Position', function() {
        updateRegisterFilterWiseTable();
    });

    $(document).on('change', '#RegisterCreateDatePickerFilter', function()
    {
        updateRegisterFilterWiseTable();
    });


    document.getElementById('RegisterCreateDatePickerFilter').addEventListener('input', function () {
            let rawDate = this.value; // Format: YYYY-MM-DD
            if (rawDate) {
                let parts = rawDate.split('-');
                this.value = `${parts[2]}-${parts[1]}-${parts[0]}`; // Converts to DD-MM-YYYY
            }
        });
        function updateRegisterFilterWiseTable()
        {
            var search = $(".search").val();
            var Poitions = $(".Position").val();
            var DatePickerFilter = $("#RegisterCreateDatePickerFilter").val();
            let isDetailedView = $(".btn-detailed").hasClass("active");
            let sendclass = isDetailedView ? 'Detailed' : 'Normal';

            $.ajax({
                url: "<?php echo e(route('resort.timeandattendance.ResigterRosterSearch')); ?>",
                type: "get",
                data: {"_token":"<?php echo e(csrf_token()); ?>","search":search,"Poitions":Poitions,"date":DatePickerFilter,"monthly":true,"sendclass":sendclass},
                success: function (response)
                {
                    if (response.success)
                    {
                        $(".filtertaleData").html(response.view);

                        // Reinitialize tooltips if Normal View
                        if (!isDetailedView) {
                            setTimeout(function() {
                                initAttendanceTooltips();
                            }, 100);
                        }
                    }
                    else
                    {
                        toastr.error(response.message, "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                },
                error: function(response) {
                    var errors = response.responseJSON;
                    var errs = '';
                    if (errors && errors.errors) {
                        $.each(errors.errors, function(key, error) {
                            errs += error + '<br>';
                        });
                    } else {
                        errs = 'An error occurred while filtering data.';
                    }
                    toastr.error(errs, { positionClass: 'toast-bottom-right' });
                }
            });
        }
    $(document).on('click', '#clearFilter', function() {
        $(".search").val('');
        $("#RegisterCreateDatePickerFilter").val('');
        $("#department").val('').trigger('change');
        $("#position").val('').trigger('change');
        updateRegisterFilterWiseTable();
    });

    </script>
    <?php $__env->stopSection(); ?>

<?php echo $__env->make('resorts.layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\wamp64\www\Wisdom-Ai\HRVMS-Wisdom-AI\resources\views/resorts/timeandattendance/attandanceregister/index.blade.php ENDPATH**/ ?>