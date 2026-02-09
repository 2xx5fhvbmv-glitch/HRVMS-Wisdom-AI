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
                            <span>Time And Attendance </span>
                            <h1><?php echo e($page_title); ?></h1>
                        </div>
                    </div>
                </div>
            </div>
            <div>
                <div class="card ">
                <form id="DutyRosterForm" class="<?php if(Common::checkRouteWisePermission('resort.timeandattendance.CreateDutyRoster',config('settings.resort_permissions.create')) == false): ?> d-none <?php endif; ?>">
                    <?php echo csrf_field(); ?>
                    <div class="row g-xl-4 g-3 mb-3">
                        <div class="col-lg-4 col-md-6 createDuty-emp">
                            <div class="mb-md-4 mb-3">
                                <label for="select-emp" class="form-label">SELECT EMPLOYEE</label>
                                    <select class="form-select" name="Emp_id[]" id="Employee" multiple>
                                        <?php if($employees->isNotEmpty()): ?>
                                            <?php $__currentLoopData = $employees; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $e): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <option value="<?php echo e($e->id); ?>"><?php echo e(ucfirst($e->first_name . ' ' . $e->last_name)); ?></option>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        <?php endif; ?>
                                    </select>
                            </div>
                            <div class="createduty-Append">

                            </div>
                        </div>
                        <div class="col-lg-4 col-md-6 ">
                            <div class="card-themeSkyblue h-100 overflow-hidden">
                                <div id="datapicker" class="configTimeAtten-dateRange">
                                    <!-- Hidden input field to attach the calendar to -->
                                    <input type="hidden" name="hiddenInput" id="hiddenInput">
                                    <input type="hidden" name="s" id="s">
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
                                        <input type="text" class="form-control datepicker" name="MakeShift" disabled id="MakeShift" placeholder="10 Sep - 14 Sep">


                                    </div>
                                    <div class="col-lg-12 col-sm-6">
                                        <select class="form-select select2t-none" id="Shift"
                                            aria-label="Default select example" name="Shift">
                                            <option></option> <!-- Leave this blank for the placeholder -->
                                            <?php if($ShiftSettings->isNotEmpty()): ?>
                                                <?php $__currentLoopData = $ShiftSettings; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                        <?php

                                                        ?>
                                                    <option value="<?php echo e($s->id); ?>"  data-totalHrs="<?php echo e($s->TotalHours ?? ''); ?>" data-startTime="<?php echo e($s->StartTime ?? ''); ?>" data-endTime="<?php echo e($s->EndTime ?? ''); ?>"> <?php echo e(ucfirst($s->ShiftName)); ?></option>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                    <div class="col-12 hideoverTimeTr">
                                        <a href="javascript:void(0)" class="a-link addOvertime-modal d-block mb-3">Add Overtime</a>
                                        <div id="overtimeSummary" class="mb-2"></div>
                                    </div>
                                    <div class="col-lg-12 col-sm-6">
                                        <label class="form-label">Select Day Off Dates</label>
                                        <input type="text" class="form-control" id="DayOffDates" name="DayOffDates" placeholder="Click to select day off dates" readonly style="background-color: white; cursor: pointer;">
                                        <small class="text-muted">Click to select multiple dates</small>
                                    </div>
                                    <div class="col-12">
                                        <div class="bg-white">
                                            <p>Net Shift Hours:</p>
                                            <small class="text-muted">(After Day Off Deduction)</small>
                                            <input type="hidden" name="TotalHours" id="TotalHoursInput" value="">
                                            <h5 id="TotalHours">0</h5>
                                        </div>
                                    </div>
                                    <div class="col-12 d-none">
                                        <div class="bg-white text-danger">
                                            <p>Day Off Deduction:</p>
                                            <h5 id="DayOffDeduction" class="text-danger">0</h5>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="bg-white">
                                            <p>Overtime Total Hours:</p>
                                            <h5 id="OvertimeTotalHours">0</h5>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="bg-white" style="background-color: #e3f2fd !important;">
                                            <p><strong>Final Total Hours:</strong></p>
                                            <input type="hidden" name="FinalTotalHours" id="FinalTotalHoursInput" value="">
                                            <h5 id="FinalTotalHours" class="fw-bold">0</h5>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" name="resort_id" value="<?php echo e($resort_id); ?>" >

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
                                        <input type="search" class="form-control search" placeholder="Search" />
                                        <i class="fa-solid fa-search"></i>
                                    </div>
                                </div>
                                
                                <div class="col-xl-2 col-md-4 col-sm-4 col-6">
                                    <select class="form-select" name="Position" id="Position">
                                        <option ></option>
                                        <?php if($ResortPosition->isNotEmpty()): ?>

                                            <?php $__currentLoopData = $ResortPosition; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <option value="<?php echo e($p->id); ?>"><?php echo e(ucfirst($p->position_title)); ?></option>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        <?php endif; ?>
                                    </select>
                                </div>
                                <div class="col-xl-2 col-md-4 col-sm-4 col-6">
                                    <input type="text"  class="form-control  datepicker" id="DutyRosterCreateDatePickerFilter" placeholder="Select Date">

                                </div>

                                <div class="col-xl-2 col-md-4 col-sm-4 col-6">
                                   <button class="btn btn-themeBlue btn-sm" id="clearFilter">Clear Filter</button>
                                </div>

                                <div class="col-auto ms-auto">
                                    <a href="javascript:void(0)" class="btn btn-weekly active">Weekly</a>
                                    <a href="javascript:void(0)" class="btn btn-monthly ">Monthly</a>
                                </div>
                            </div>
                        </div>
                        <div class="appendData">
                            <div class="weekly-main">
                                <div class="table-responsive mb-4">
                                    <table id="createDutyWeeklyTable" class="table table-createDutyWeekly mb-1">
                                        <thead>
                                            <tr>
                                                <th>Employee Name</th>
                                                <?php $__currentLoopData = $headers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $d): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                    <?php
                                                        $currentDate = isset($d['full_date']) ? $d['full_date']->format('Y-m-d') : date('Y-m-d', strtotime($d['date']));
                                                        $isPublicHoliday = isset($publicHolidays) && in_array($currentDate, $publicHolidays);
                                                    ?>
                                                    <th class="<?php echo e($isPublicHoliday ? 'public-holiday-header' : ''); ?>"><?php echo e($d['date']); ?> <span><?php echo e($d['day']); ?></span></th>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
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
                                                                        <span class="fw-600"><?php echo e(ucfirst($r->first_name .' '. $r->last_name)); ?></span>
                                                                        <span class="badge badge-white"><?php echo e($r->Emp_id); ?></span>
                                                                    </p>
                                                                    <span><?php echo e(ucfirst($r->position_title)); ?></span>
                                                                </div>
                                                            </div>
                                                        </td>

                                                        <?php

                                                            $RosterInternalData = Common::GetRosterdata($resort_id, $r->duty_roster_id, $r->emp_id, $WeekstartDate, $WeekendDate , $startOfMonth,$endOfMonth,"weekly");
                                                        //    dd( $RosterInternalData,$WeekstartDate, $WeekendDate);
                                                            $totalHours = 0;
                                                            $dataCount = $RosterInternalData->count();
                                                            $minColumns = 7; // Minimum number of columns to account for all days of the week
                                                        ?>

                                                        <?php $__currentLoopData = $headers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $header): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                            <?php
                                                                // Use full_date if available (Carbon object), otherwise parse date string
                                                                $currentDate = isset($header['full_date']) ? $header['full_date']->format('Y-m-d') : date('Y-m-d', strtotime($header['date']));
                                                                $isPublicHoliday = isset($publicHolidays) && in_array($currentDate, $publicHolidays);
                                                                $shiftData = $RosterInternalData->firstWhere('date', $currentDate);
                                                                
                                                                // Check for leave on this date
                                                                $employeeLeave = \App\Models\EmployeeLeave::join('leave_categories as t4', 't4.id', '=', 'employees_leaves.leave_category_id')
                                                                    ->where('employees_leaves.emp_id', $r->emp_id)
                                                                    ->where('employees_leaves.status', 'Approved')
                                                                    ->whereDate('employees_leaves.from_date', '<=', $currentDate)
                                                                    ->whereDate('employees_leaves.to_date', '>=', $currentDate)
                                                                    ->first(['t4.color', 't4.leave_type', 't4.leave_category', 'employees_leaves.from_date', 'employees_leaves.to_date']);
                                                                
                                                                $toatalHoursForDay = 0;
                                                                if ($shiftData && !$employeeLeave)
                                                                {
                                                                    if($shiftData->Status!= 'DayOff')
                                                                    {
                                                                        $startTime = \Carbon\Carbon::parse($shiftData->StartTime);
                                                                        $endTime = \Carbon\Carbon::parse($shiftData->EndTime);
                                                                        $hours_abc = $startTime->diffInHours($endTime);
                                                                        $toatalHoursForDay = $hours_abc;
                                                                        $totalHours += $toatalHoursForDay; // Update total hours worked
                                                                    }else{
                                                                        $toatalHoursForDay = 0;
                                                                    }
                                                                }
                                                            ?>

                                                            <td class="<?php echo e($isPublicHoliday ? 'public-holiday-cell' : ''); ?>">
                                                                <?php if($employeeLeave): ?>
                                                                    
                                                                    <div class="createDuty-tableBlock" style="border-color: <?php echo e($employeeLeave->color ?? '#ccc'); ?>; border-width: 2px;">
                                                                        <div class="d-flex">
                                                                            <div>
                                                                                <p class="fw-600"><?php echo e($employeeLeave->leave_type ?? 'Leave'); ?></p>
                                                                                <?php if($employeeLeave->leave_category): ?>
                                                                                    <span class="small"><?php echo e($employeeLeave->leave_category); ?></span>
                                                                                <?php endif; ?>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                <?php elseif($shiftData): ?>
                                                                    
                                                                    <div class="createDuty-tableBlock <?php echo e($shiftData->ShiftNameColor ?? ''); ?>">
                                                                        <div class="d-flex">
                                                                            <div>
                                                                                <?php
                                                                                    $startTime = \Carbon\Carbon::parse($shiftData->StartTime);
                                                                                    $endTime = \Carbon\Carbon::parse($shiftData->EndTime);
                                                                                ?>
                                                                                <p><?php echo e($startTime->format('h:i A')); ?> - <?php echo e($endTime->format('h:i A')); ?></p>
                                                                                <span><?php echo e($shiftData->ShiftName); ?></span>
                                                                            </div>
                                                                            <div class="badge"><?php echo e($toatalHoursForDay); ?> <?php echo e($shiftData->color ?? ''); ?> hrs</div>
                                                                        </div>
                                                                        <div class="d-flex ot-details">
                                                                            <?php if($shiftData): ?>
                                                                                <p>OT: <?php echo e($shiftData->OverTime ?? 0); ?> hr</p>
                                                                            <?php endif; ?>
                                                                            <p>
                                                                                <?php if($shiftData->Status!= 'DayOff'): ?>
                                                                                    <button class="editIcon-btn editdutyRoster"
                                                                                            data-date="<?php echo e(date('d/m/Y', strtotime($header['date']))); ?>"
                                                                                            data-Shift_id="<?php echo e($shiftData->Shift_id ?? ''); ?>"
                                                                                            data-OverTime="<?php echo e($shiftData->OverTime ?? 0); ?>"
                                                                                            data-DayOfDate="<?php echo e($shiftData->DayOfDate ?? ''); ?>"
                                                                                            data-Attd_id="<?php echo e($shiftData->Attd_id ?? ''); ?>"
                                                                                            data-DayWiseTotalHours="<?php echo e($toatalHoursForDay ?? ''); ?>">
                                                                                        <i class="fa fa-edit"></i>
                                                                                    </button>
                                                                                <?php else: ?>
                                                                                    DayOff
                                                                                <?php endif; ?>
                                                                            </p>
                                                                        </div>
                                                                    </div>
                                                                <?php else: ?>
                                                                    
                                                                    <div class="createDuty-tableBlock">
                                                                        <div class="createDuty-empty">No Shift Assigned</div>
                                                                    </div>
                                                                <?php endif; ?>
                                                            </td>
                                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>



                                                        <td>Total Hrs: <span><?php echo e($totalHours); ?></span></td>
                                                    </tr>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="<?php echo e(count($headers) + 2); ?>" style="text-align: center">No Records Found..</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>

                                </div>
                                
                                <div class="pagination-custom"> <?php echo e($Rosterdata->links()); ?></div>

                            </div>
                            <div class="monthly-main  d-none">
                                <div class="table-responsive mb-4">
                                    <table id="" class="table table-bordered table-createDutymonthly mb-1">


                                        <thead>
                                            <tr>
                                                <th>Employee Name</th>
                                                <?php if(!empty($monthwiseheaders)): ?>
                                                    <?php $__currentLoopData = $monthwiseheaders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $h): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                        <?php
                                                            // Ensure date is in Y-m-d format
                                                            if (isset($h['date'])) {
                                                                $currentDate = \Carbon\Carbon::parse($h['date'])->format('Y-m-d');
                                                            } else {
                                                                $currentDate = \Carbon\Carbon::createFromDate($startOfMonth->year, $startOfMonth->month, $h['day'])->format('Y-m-d');
                                                            }
                                                            $isPublicHoliday = isset($publicHolidays) && in_array($currentDate, $publicHolidays);
                                                        ?>
                                                        <th class="<?php echo e($isPublicHoliday ? 'public-holiday-header' : ''); ?>"><?php echo e($h['day']); ?> <span><?php echo e($h['dayname']); ?></span></th>
                                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                <?php endif; ?>


                                                <th>Summary</th>
                                                <?php if($LeaveCategory->isNotEmpty()): ?>

                                            <?php endif; ?>

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
                                                            $RosterInternalDataMonth = Common::GetRosterdata($resort_id, $r->duty_roster_id, $r->emp_id, $WeekstartDate, $WeekendDate,  $startOfMonth,$endOfMonth,"Monthwise");
                                                        ?>

                                                        <?php
                                                            $totalHoursMonth = 0;
                                                        ?>
                                                        <?php $__currentLoopData = $monthwiseheaders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $h): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                        <?php
                                                            // Ensure date is in Y-m-d format
                                                            if (isset($h['date'])) {
                                                                $formattedDate = \Carbon\Carbon::parse($h['date'])->format('Y-m-d');
                                                            } else {
                                                                $formattedDate = \Carbon\Carbon::createFromDate($startOfMonth->year, $startOfMonth->month, $h['day'])->format('Y-m-d');
                                                            }
                                                            $isPublicHoliday = isset($publicHolidays) && in_array($formattedDate, $publicHolidays);
                                                            $shiftData = $RosterInternalDataMonth->firstWhere('date', $formattedDate);
                                                            
                                                            // Check for leave on this date
                                                            $employeeLeave = \App\Models\EmployeeLeave::join('leave_categories as t4', 't4.id', '=', 'employees_leaves.leave_category_id')
                                                                ->where('employees_leaves.emp_id', $r->emp_id)
                                                                ->where('employees_leaves.status', 'Approved')
                                                                ->whereDate('employees_leaves.from_date', '<=', $formattedDate)
                                                                ->whereDate('employees_leaves.to_date', '>=', $formattedDate)
                                                                ->first(['t4.color', 't4.leave_type', 't4.leave_category', 'employees_leaves.from_date', 'employees_leaves.to_date']);

                                                            $toatalHoursForDay = 0;
                                                            $startTime = null;
                                                            $endTime = null;
                                                            if ($shiftData && !$employeeLeave)
                                                            {
                                                                if($shiftData->Status != 'DayOff')
                                                                {
                                                                    $startTime = \Carbon\Carbon::parse($shiftData->StartTime);
                                                                    $endTime = \Carbon\Carbon::parse($shiftData->EndTime);
                                                                    $hours_abc = $startTime->diffInHours($endTime);
                                                                    $toatalHoursForDay = $hours_abc;
                                                                    $totalHoursMonth += $toatalHoursForDay;
                                                                }else{
                                                                    $toatalHoursForDay = 0;
                                                                    // Still show shift times for DayOff if available
                                                                    if(isset($shiftData->StartTime) && isset($shiftData->EndTime)) {
                                                                        $startTime = \Carbon\Carbon::parse($shiftData->StartTime);
                                                                        $endTime = \Carbon\Carbon::parse($shiftData->EndTime);
                                                                    }
                                                                }
                                                            }
                                                        ?>

                                                            <td class="<?php echo e($isPublicHoliday ? 'public-holiday-cell' : ''); ?>">
                                                                <?php if($employeeLeave): ?>
                                                                    
                                                                    <div class="createDuty-tableBlock" style="border-color: <?php echo e($employeeLeave->color ?? '#ccc'); ?>; border-width: 2px;">
                                                                        <div class="d-flex">
                                                                            <div>
                                                                                <p class="fw-600"><?php echo e($employeeLeave->leave_type ?? 'Leave'); ?></p>
                                                                                <?php if($employeeLeave->leave_category): ?>
                                                                                    <span class="small"><?php echo e($employeeLeave->leave_category); ?></span>
                                                                                <?php endif; ?>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                <?php elseif($shiftData): ?>
                                                                    
                                                                    <div class="createDuty-tableBlock <?php echo e($shiftData->ShiftNameColor ?? ''); ?>">
                                                                        <div class="d-flex">
                                                                            <div>
                                                                                <?php
                                                                                    if(!$startTime && isset($shiftData->StartTime)) {
                                                                                        $startTime = \Carbon\Carbon::parse($shiftData->StartTime);
                                                                                    }
                                                                                    if(!$endTime && isset($shiftData->EndTime)) {
                                                                                        $endTime = \Carbon\Carbon::parse($shiftData->EndTime);
                                                                                    }
                                                                                ?>
                                                                                <p><?php if($startTime && $endTime): ?><?php echo e($startTime->format('h:i A')); ?> - <?php echo e($endTime->format('h:i A')); ?><?php endif; ?></p>
                                                                                <span><?php echo e($shiftData->ShiftName ?? ''); ?></span>
                                                                            </div>
                                                                            <div class="badge"><?php echo e($toatalHoursForDay); ?> hrs</div>
                                                                        </div>
                                                                        <div class="d-flex ot-details">
                                                                            <?php if($shiftData): ?>
                                                                                <p>OT: <?php echo e($shiftData->OverTime ?? 0); ?> hr</p>
                                                                            <?php endif; ?>
                                                                            <p>
                                                                                <?php if($shiftData->Status != 'DayOff'): ?>
                                                                                    <button class="editIcon-btn editdutyRoster"
                                                                                            data-date="<?php echo e(date('d/m/Y', strtotime($h['date']))); ?>"
                                                                                            data-Shift_id="<?php echo e($shiftData->Shift_id ?? ''); ?>"
                                                                                            data-OverTime="<?php echo e($shiftData->OverTime ?? 0); ?>"
                                                                                            data-DayOfDate="<?php echo e($shiftData->DayOfDate ?? ''); ?>"
                                                                                            data-Attd_id="<?php echo e($shiftData->Attd_id ?? ''); ?>"
                                                                                            data-DayWiseTotalHours="<?php echo e($toatalHoursForDay ?? ''); ?>">
                                                                                        <i class="fa fa-edit"></i>
                                                                                    </button>
                                                                                <?php else: ?>
                                                                                    DayOff
                                                                                <?php endif; ?>
                                                                            </p>
                                                                        </div>
                                                                    </div>
                                                                <?php else: ?>
                                                                    
                                                                    <div class="createDuty-tableBlock">
                                                                        <div class="createDuty-empty">No Shift Assigned</div>
                                                                    </div>
                                                                <?php endif; ?>
                                                            </td>
                                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                                                        <td>Total Hrs: <span><?php echo e($totalHoursMonth); ?></span></td>


                                                    </tr>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                            <?php endif; ?>
                                        </tbody>



                                    </table>
                                </div>
                                <div class="pagination-custom">
                                    <nav aria-label="Page navigation example">
                                        <?php echo e($Rosterdata->links()); ?>

                                    </nav>
                                </div>


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
                    <?php echo csrf_field(); ?>
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
                                    <?php if($ShiftSettings->isNotEmpty()): ?>
                                        <?php $__currentLoopData = $ShiftSettings; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>

                                            <option value="<?php echo e($s->id); ?>"  data-totalHrs="<?php echo e($s->TotalHours); ?>"> <?php echo e(ucfirst($s->ShiftName)); ?> </option>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <div class="col-md-12 mt-3 ShiftOverTimetr">
                                <lable>Over Time </lable>
                                <input type="text" class="form-control ShiftOverTime" id="ShiftOverTime" name="ShiftOverTime" placeholder="Over Time">
                            </div>
                            <div class="col-md-12 mt-3">
                                <label>Select Day Off Dates</label>
                                <input type="text" class="form-control" id="DayOffDatesModel" name="DayOffDatesModel" placeholder="Click to select day off dates" readonly style="background-color: white; cursor: pointer;">
                                <small class="text-muted">Click to select multiple dates</small>
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

    <!-- Add Overtime Modal -->
    <div class="modal fade" id="addOvertimeModal" tabindex="-1" aria-labelledby="addOvertimeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addOvertimeModalLabel">Add Overtime for Employees</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Select Employees for Overtime</label>
                        <select class="form-select" id="OvertimeEmployees" multiple>
                            <!-- Will be populated dynamically -->
                        </select>
                    </div>
                    <div id="overtimeEmployeesList" class="mt-3">
                        <!-- Dynamic list of selected employees with overtime inputs and day selection -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-themeGray" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-themeBlue" id="saveOvertimeBtn">Save Overtime</button>
                </div>
            </div>
        </div>
    </div>
    <?php $__env->stopSection(); ?>

<?php $__env->startSection('import-css'); ?>
<style>
    /* Flatpickr custom styling for selected dates in multiple mode */
    .flatpickr-day.selected,
    .flatpickr-day.selected:hover,
    .flatpickr-day.selected:focus {
        background: #007bff !important;
        border-color: #007bff !important;
        color: #fff !important;
        font-weight: bold;
    }

    .flatpickr-day.selected.startRange,
    .flatpickr-day.selected.endRange {
        background: #0056b3 !important;
        border-color: #0056b3 !important;
    }

    /* Available dates - make them clearly visible */
    .flatpickr-day:not(.disabled):not(.selected) {
        background: #fff;
        color: #333;
        cursor: pointer;
        font-weight: 500;
    }

    /* Hover effect for available dates */
    .flatpickr-day:hover:not(.disabled):not(.selected) {
        background: #e3f2fd !important;
        border-color: #90caf9 !important;
        cursor: pointer;
    }

    /* Styling for today's date */
    .flatpickr-day.today {
        border-color: #007bff !important;
        position: relative;
        font-weight: bold;
    }

    .flatpickr-day.today:not(.selected) {
        background: #fff;
        color: #007bff;
    }

    /* Disabled dates styling - make them obviously disabled */
    .flatpickr-day.disabled,
    .flatpickr-day.disabled:hover,
    .flatpickr-day.flatpickr-disabled {
        color: #e0e0e0 !important;
        cursor: not-allowed !important;
        background: #fafafa !important;
        text-decoration: line-through;
        opacity: 0.4;
    }

    /* Prev/next month dates */
    .flatpickr-day.prevMonthDay,
    .flatpickr-day.nextMonthDay {
        color: #bdbdbd !important;
    }

    .flatpickr-day.prevMonthDay.disabled,
    .flatpickr-day.nextMonthDay.disabled {
        color: #e0e0e0 !important;
        opacity: 0.3;
    }

    /* Calendar container z-index */
    .flatpickr-calendar {
        z-index: 9999 !important;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        border-radius: 8px;
    }

    /* Better visibility for month selector */
    .flatpickr-monthDropdown-months {
        background: white;
    }

    /* Style for the input field when picker is open */
    .flatpickr-input.active,
    #DayOffDates.active,
    #DayOffDatesModel.active {
        border-color: #007bff !important;
        box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25) !important;
    }

    /* Month navigation arrows */
    .flatpickr-months .flatpickr-prev-month:hover,
    .flatpickr-months .flatpickr-next-month:hover {
        color: #007bff;
    }

    /* Current month highlight */
    .flatpickr-current-month .flatpickr-monthDropdown-months .flatpickr-monthDropdown-month {
        background: white;
    }
</style>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('import-scripts'); ?>

<script type="text/javascript">
    // new DataTable('#example');



    // tooltip
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });


    // Declare day off pickers at global scope so they're accessible to all functions
    var dayOffPicker = null;
    var dayOffPickerModel = null;

    // date range picker
    $(document).ready(function ()
    {
        document.addEventListener('DOMContentLoaded', function () {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
        $('[data-bs-toggle="tooltip"]').tooltip();

        // Overtime inputs are now handled in the modal, so this is removed
        var shiftOverTimePicker =  flatpickr(".shiftdate", {
            enableTime: true,
            noCalendar: true,
            dateFormat: "h:i", // 12-hour format without AM/PM
            time_24hr: false,  // Ensures 12-hour format
            minuteIncrement: 1, // Allows 1-minute steps

        });
        $('#Employee').select2({
            placeholder: "Select Employees", // Placeholder text
            allowClear: true, // Adds a clear (X) button to reset the dropdown
            multiple: true // Enable multiple selection
        });

        $('#OvertimeEmployees').select2({
            placeholder: "Select Employees for Overtime",
            allowClear: true,
            multiple: true
        });
        $('#Shift').select2({
            placeholder: "Select a Shift", // Placeholder text
            allowClear: true // Adds a clear (X) button to reset the dropdown
        });

        $('#Position').select2({
            placeholder: "Select a Position", // Placeholder text
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
        $('.datepicker').datepicker({
            format: 'dd/mm/yyyy',
            autoclose: true,      // Close the picker after selection
            todayHighlight: true  // Highlight today's date
        });

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

        // Initialize with default date range
        updateDateText(moment(), moment().add(7, 'days'));
            function updateDateText(start, end) {
                let startDate = start.format("YYYY-MM-DD").toString();
                let endDate = end.format("YYYY-MM-DD").toString();

                let startDate1 = start.format("DD MMM").toString();
                let endDate1 = end.format("DD MMM").toString();

                $("#MakeShift").val(startDate1 + " - " + endDate1);

                console.log('Date range changed:', startDate, 'to', endDate);

                // Clear existing day off dates before reinitializing
                if(dayOffPicker) {
                    dayOffPicker.clear();
                    dayOffPicker.destroy();
                    dayOffPicker = null;
                }

                // Small delay to ensure DOM is ready
                setTimeout(function() {
                    // Reinitialize day off picker with new date range
                    initializeDayOffPicker();
                }, 50);

                // Recalculate totals when date changes
                calculateAllTotals();



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
            // Recalculate totals when date range changes
            calculateAllTotals();
        });

        // Initialize multi-date picker for day off dates
        function initializeDayOffPicker() {
            // Get the date range
            let hiddenInput = $("#hiddenInput").val();
            if(!hiddenInput) return;

            let hiddenInputArray = hiddenInput.split(' - ');
            let startDateStr = hiddenInputArray[0].trim();
            let endDateStr = hiddenInputArray[1].trim();

            // Parse dates - format is m/d/Y
            let startParts = startDateStr.split('/');
            let endParts = endDateStr.split('/');
            let minDate = new Date(parseInt(startParts[2]), parseInt(startParts[0]) - 1, parseInt(startParts[1]));
            let maxDate = new Date(parseInt(endParts[2]), parseInt(endParts[0]) - 1, parseInt(endParts[1]));

            // Generate array of enabled dates within the range
            let enabledDates = [];
            let currentDate = new Date(minDate);
            while(currentDate <= maxDate) {
                enabledDates.push(new Date(currentDate));
                currentDate.setDate(currentDate.getDate() + 1);
            }

            // Clear the input value
            $("#DayOffDates").val('');

            // Destroy existing picker if any
            if(dayOffPicker) {
                dayOffPicker.destroy();
            }

            // Initialize flatpickr with multiple date selection
            dayOffPicker = flatpickr("#DayOffDates", {
                mode: "multiple",
                dateFormat: "Y-m-d",
                minDate: minDate,
                maxDate: maxDate,
                enable: enabledDates, // Only enable dates within the range
                inline: false,
                clickOpens: true,
                allowInput: false,
                conjunction: ", ",
                defaultDate: null,
                onChange: function(selectedDates, dateStr, instance) {
                    console.log('=== Day Off Picker onChange Event ===');
                    console.log('Selected dates count:', selectedDates.length);
                    console.log('Selected dates:', selectedDates);
                    console.log('Date string:', dateStr);
                    console.log('Input field updated to:', $('#DayOffDates').val());

                    // Small delay to ensure the input value is updated
                    setTimeout(function() {
                        console.log('Calling calculateAllTotals from onChange...');
                        calculateAllTotals();
                    }, 100);
                },
                onOpen: function(selectedDates, dateStr, instance) {
                    $('#DayOffDates').addClass('active');
                    console.log('Calendar opened. Available range:', minDate, 'to', maxDate);
                },
                onClose: function(selectedDates, dateStr, instance) {
                    $('#DayOffDates').removeClass('active');
                },
                onReady: function(selectedDates, dateStr, instance) {
                    instance.calendarContainer.style.zIndex = 9999;
                    // Jump to the start date month
                    instance.jumpToDate(minDate);
                    instance.redraw();
                },
                onMonthChange: function(selectedDates, dateStr, instance) {
                    // Redraw when month changes to ensure proper styling
                    instance.redraw();
                },
                locale: {
                    firstDayOfWeek: 1
                }
            });

            console.log('Day off picker initialized with range:', minDate, 'to', maxDate);
        }

        // Initialize day off picker for modal
        function initializeDayOffPickerModel() {
            // Clear the input value
            $("#DayOffDatesModel").val('');

            // Destroy existing picker if any
            if(dayOffPickerModel) {
                dayOffPickerModel.destroy();
            }

            // Initialize flatpickr with multiple date selection
            dayOffPickerModel = flatpickr("#DayOffDatesModel", {
                mode: "multiple",
                dateFormat: "Y-m-d",
                inline: false,
                clickOpens: true,
                allowInput: false,
                conjunction: ", ",
                onChange: function(selectedDates, dateStr, instance) {
                    console.log('Day off dates selected (modal):', selectedDates);
                },
                onOpen: function(selectedDates, dateStr, instance) {
                    $('#DayOffDatesModel').addClass('active');
                },
                onClose: function(selectedDates, dateStr, instance) {
                    $('#DayOffDatesModel').removeClass('active');
                },
                onReady: function(selectedDates, dateStr, instance) {
                    instance.calendarContainer.style.zIndex = 10000;
                    instance.redraw();
                },
                locale: {
                    firstDayOfWeek: 1
                }
            });
        }

        // Initialize model picker on load (main picker is initialized in updateDateText)
        initializeDayOffPickerModel();

        // Ensure day off picker opens on click
        $(document).on('click', '#DayOffDates', function(e) {
            e.preventDefault();
            if(dayOffPicker) {
                if(dayOffPicker.isOpen !== true) {
                    dayOffPicker.open();
                } else {
                    // If already open, redraw to refresh
                    dayOffPicker.redraw();
                }
            } else {
                console.warn('Day off picker not initialized');
                initializeDayOffPicker();
            }
        });

        // Ensure day off picker model opens on click
        $(document).on('click', '#DayOffDatesModel', function() {
            if(dayOffPickerModel && dayOffPickerModel.isOpen !== true) {
                dayOffPickerModel.open();
            }
        });

        $('#DutyRosterForm').validate({
            rules: {
                'Emp_id[]': {
                    required: true,
                },
                MakeShift:{
                    required: true,
                },
                Shift:{
                    required: true,
                },
                DayOffDates:{
                    required: true,
                }

            },
            messages: {
                'Emp_id[]': {
                    required: "Please Select at least one Employee.",
                },
                MakeShift:{
                    required:"Please select Shift Date.",
                },
                Shift:{
                    required: "Please select Shift .",
                },
                DayOffDates:{
                    required: "Please select Day Off dates.",
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

                // Add overtime data as JSON (includes days for each employee)
                // Ensure employee IDs are strings for consistency
                let overtimeDataToSend = {};
                for(let empId in employeeOvertimeData) {
                    overtimeDataToSend[String(empId)] = employeeOvertimeData[empId];
                }

                formData.append('employeeOvertime', JSON.stringify(overtimeDataToSend));

                // Add final total hours
                formData.append('FinalTotalHours', $('#FinalTotalHoursInput').val());

                // Debug: Log the data being sent
                console.log('Overtime Data:', overtimeDataToSend);
                console.log('Final Total Hours:', $('#FinalTotalHoursInput').val());

                $.ajax({
                    url: "<?php echo e(route('resort.timeandattendance.StoreDutyRoster')); ?>", // Ensure route is correct
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
                    error: function(xhr, status, error) {
                        if (xhr.status === 422)
                        {
                            var responseData = xhr.responseJSON || {};
                            var errors = responseData.errors || {};
                            var errs = '';
                            
                            // Debug: Log the response to console
                            console.log('Validation Error Response:', responseData);
                            console.log('Errors object:', errors);
                            
                            if (typeof errors === 'object' && Object.keys(errors).length > 0) {
                                $.each(errors, function (field, messages) {
                                    if (Array.isArray(messages)) {
                                        $.each(messages, function (index, message) {
                                            errs += message + '<br>'; // Append each message
                                        });
                                    } else if (typeof messages === 'string') {
                                        errs += messages + '<br>';
                                    }
                                });
                            }
                            
                            // If no errors found in errors object, check for message
                            if (!errs && responseData.message) {
                                errs = responseData.message;
                            }
                            
                            // If still no error message, show generic message with details
                            if (!errs) {
                                errs = 'Validation failed. Please check your input.';
                                console.error('No error messages found in response:', responseData);
                            }
                            
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

            // Handle day off dates - convert from old format if needed
            let DayOffDates = $(this).attr('data-DayOffDates') || '';
            if(dayOffPickerModel) {
                if(DayOffDates) {
                    // If it's a comma-separated list of dates
                    let datesArray = DayOffDates.split(',').map(d => d.trim());
                    dayOffPickerModel.setDate(datesArray, false);
                } else {
                    dayOffPickerModel.clear();
                }
            }
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
                    DayOffDatesModel: {
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
                    DayOffDatesModel: {
                        required: "Please Select Day Off dates",
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
                        url: "<?php echo e(route('resort.timeandattendance.UpdateDutyRoster')); ?>",
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
    // Store overtime data for employees - structure: {empId: {overtime: '00:00', days: ['2024-01-09', '2024-01-10']}}
    let employeeOvertimeData = {};
    let availableDates = []; // Store available dates from date range

    // Function to get all dates in the selected range
    function getDateRange() {
        let hiddenInput = $("#hiddenInput").val();
        if(!hiddenInput) return [];

        let hiddenInputArray = hiddenInput.split(' - ');
        let startDateStr = hiddenInputArray[0].trim();
        let endDateStr = hiddenInputArray[1].trim();

        // Parse dates - format is m/d/Y
        let startParts = startDateStr.split('/');
        let endParts = endDateStr.split('/');
        let startDate = new Date(parseInt(startParts[2]), parseInt(startParts[0]) - 1, parseInt(startParts[1]));
        let endDate = new Date(parseInt(endParts[2]), parseInt(endParts[0]) - 1, parseInt(endParts[1]));

        let dates = [];
        let currentDate = new Date(startDate);
        while(currentDate <= endDate) {
            dates.push(new Date(currentDate));
            currentDate.setDate(currentDate.getDate() + 1);
        }
        return dates;
    }

    $('.addOvertime-modal').on('click', function() {
        let makeshiftdate = $("#MakeShift").val();
        if(makeshiftdate=="")
        {
            toastr.error("Please Select Calendar", "Error", {
                positionClass: 'toast-bottom-right'
            });
            return false;
        }

        let selectedEmployees = $('#Employee').val();
        if(!selectedEmployees || selectedEmployees.length === 0) {
            toastr.error("Please Select Employees First", "Error", {
                positionClass: 'toast-bottom-right'
            });
            return false;
        }

        // Get available dates
        availableDates = getDateRange();
        if(availableDates.length === 0) {
            toastr.error("Please Select Date Range", "Error", {
                positionClass: 'toast-bottom-right'
            });
            return false;
        }

        // Populate overtime employees dropdown with selected employees
        let employeeOptions = '';
        let employeeData = {};
        let selectedEmployeeIds = [];

        $('#Employee option:selected').each(function() {
            let empId = $(this).val();
            let empName = $(this).text();
            employeeOptions += '<option value="' + empId + '">' + empName + '</option>';
            selectedEmployeeIds.push(empId);

            // Initialize employee data with existing overtime or defaults
            if(employeeOvertimeData[empId]) {
                employeeData[empId] = {
                    name: empName,
                    overtime: employeeOvertimeData[empId].overtime || '00:00',
                    days: employeeOvertimeData[empId].days || []
                };
            } else {
                employeeData[empId] = {
                    name: empName,
                    overtime: '00:00',
                    days: []
                };
            }
        });

        // Set the dropdown options
        $('#OvertimeEmployees').html(employeeOptions);

        // Pre-select all employees in the dropdown (without triggering change to avoid filtering)
        $('#OvertimeEmployees').val(selectedEmployeeIds);

        // Manually update select2 to show selected values
        $('#OvertimeEmployees').trigger('change.select2');

        // Populate existing overtime data for all selected employees immediately
        updateOvertimeEmployeesList(employeeData);

        $('#addOvertimeModal').modal('show');
    });

    // Function to update overtime employees list with day selection
    function updateOvertimeEmployeesList(employeeData) {
        let html = '';
        for(let empId in employeeData) {
            html += '<div class="card mb-3 overtime-emp-row" data-emp-id="' + empId + '">';
            html += '<div class="card-body">';
            html += '<div class="row align-items-center mb-3">';
            html += '<div class="col-md-4">';
            html += '<label class="form-label fw-bold">' + employeeData[empId].name + '</label>';
            html += '</div>';
            html += '<div class="col-md-4">';
            html += '<label class="form-label">Overtime Hours</label>';
            html += '<input type="text" class="form-control employee-overtime-input" data-emp-id="' + empId + '" value="' + employeeData[empId].overtime + '" placeholder="00:00">';
            html += '</div>';
            html += '<div class="col-md-4 text-end">';
            html += '<button type="button" class="btn btn-sm btn-danger remove-overtime-emp" data-emp-id="' + empId + '"><i class="fa fa-times"></i> Remove</button>';
            html += '</div>';
            html += '</div>';

            // Day selection checkboxes
            html += '<div class="row">';
            html += '<div class="col-12">';
            html += '<label class="form-label">Select Days for Overtime:</label>';
            html += '<div class="d-flex flex-wrap gap-2">';

            availableDates.forEach(function(date) {
                // Format date in local timezone (not UTC) to match display
                let year = date.getFullYear();
                let month = String(date.getMonth() + 1).padStart(2, '0');
                let day = String(date.getDate()).padStart(2, '0');
                let dateStr = `${year}-${month}-${day}`;

                let dateFormatted = date.toLocaleDateString('en-US', { day: 'numeric', month: 'short' });
                let dayName = date.toLocaleDateString('en-US', { weekday: 'short' });
                let isChecked = employeeData[empId].days.includes(dateStr) ? 'checked' : '';

                html += '<div class="form-check">';
                html += '<input class="form-check-input overtime-day-checkbox" type="checkbox" data-emp-id="' + empId + '" data-date="' + dateStr + '" id="day_' + empId + '_' + dateStr + '" ' + isChecked + '>';
                html += '<label class="form-check-label" for="day_' + empId + '_' + dateStr + '">';
                html += dateFormatted + ' (' + dayName + ')';
                html += '</label>';
                html += '</div>';
            });

            html += '</div>';
            html += '</div>';
            html += '</div>';
            html += '</div>';
            html += '</div>';
        }
        $('#overtimeEmployeesList').html(html);

        // Initialize flatpickr for overtime inputs
        $('.employee-overtime-input').each(function() {
            let $input = $(this);
            let currentValue = $input.val() || '00:00';

            // Destroy existing flatpickr if any
            if ($input.data("flatpickr")) {
                $input[0]._flatpickr.destroy();
            }

            // Initialize flatpickr
            let fp = flatpickr(this, {
                enableTime: true,
                noCalendar: true,
                dateFormat: "H:i",
                time_24hr: true,
                minuteIncrement: 1,
                defaultDate: currentValue,
                onChange: function(selectedDates, dateStr, instance) {
                    // Update calculation when overtime changes
                    setTimeout(function() {
                        calculateAllTotals();
                    }, 100);
                }
            });

            // Set the value if it exists
            if(currentValue && currentValue !== '00:00') {
                try {
                    let [hours, minutes] = currentValue.split(':');
                    let date = new Date();
                    date.setHours(parseInt(hours) || 0);
                    date.setMinutes(parseInt(minutes) || 0);
                    fp.setDate(date, false);
                } catch(e) {
                    console.log('Error setting flatpickr date:', e);
                }
            }
        });

        // Add change handler for day checkboxes (remove old handlers first)
        $('.overtime-day-checkbox').off('change').on('change', function() {
            setTimeout(function() {
                calculateAllTotals();
            }, 100);
        });
    }

    // Handle overtime employees selection change
    $(document).on('change', '#OvertimeEmployees', function() {
        let selected = $(this).val() || [];
        let currentData = {};

        // Get existing data including selected days for currently visible rows
        $('.overtime-emp-row').each(function() {
            let empId = $(this).data('emp-id');
            // Only keep data for employees that are still selected
            if(selected.includes(empId)) {
                let overtime = $(this).find('.employee-overtime-input').val() || '00:00';
                let selectedDays = [];
                $(this).find('.overtime-day-checkbox:checked').each(function() {
                    selectedDays.push($(this).data('date'));
                });

                currentData[empId] = {
                    name: $('#OvertimeEmployees option[value="' + empId + '"]').text(),
                    overtime: overtime,
                    days: selectedDays
                };
            }
        });

        // Add newly selected employees that don't have existing data
        selected.forEach(function(empId) {
            if (!currentData[empId]) {
                // Check if this employee has saved overtime data
                if(employeeOvertimeData[empId]) {
                    currentData[empId] = {
                        name: $('#OvertimeEmployees option[value="' + empId + '"]').text(),
                        overtime: employeeOvertimeData[empId].overtime || '00:00',
                        days: employeeOvertimeData[empId].days || []
                    };
                } else {
                    currentData[empId] = {
                        name: $('#OvertimeEmployees option[value="' + empId + '"]').text(),
                        overtime: '00:00',
                        days: []
                    };
                }
            }
        });

        // Update the list - this will show only selected employees and remove deselected ones
        updateOvertimeEmployeesList(currentData);
    });

    // Remove employee from overtime list
    $(document).on('click', '.remove-overtime-emp', function() {
        let empId = $(this).data('emp-id');
        // Remove from dropdown selection
        $('#OvertimeEmployees option[value="' + empId + '"]').prop('selected', false);
        // Remove from saved overtime data
        delete employeeOvertimeData[empId];
        // Trigger change to update the list
        $('#OvertimeEmployees').trigger('change');
        // Recalculate totals
        calculateAllTotals();
    });

    // Save overtime data with selected days
    $('#saveOvertimeBtn').on('click', function() {
        // Get currently selected employees from dropdown
        let selectedEmployees = $('#OvertimeEmployees').val() || [];

        // Convert employee IDs to strings for consistency
        selectedEmployees = selectedEmployees.map(function(id) {
            return String(id);
        });

        // Clear all overtime data first
        employeeOvertimeData = {};

        // Only save data for employees that are currently visible (selected in dropdown)
        $('.overtime-emp-row').each(function() {
            let empId = String($(this).data('emp-id')); // Ensure string format

            // Only process if this employee is selected in the dropdown
            if(selectedEmployees.includes(empId)) {
                let overtimeInput = $(this).find('.employee-overtime-input');
                let overtime = overtimeInput.val() || '00:00';

                // If flatpickr is initialized, get the formatted value
                if(overtimeInput.data('flatpickr')) {
                    let fp = overtimeInput[0]._flatpickr;
                    if(fp.selectedDates.length > 0) {
                        let date = fp.selectedDates[0];
                        let hours = date.getHours().toString().padStart(2, '0');
                        let minutes = date.getMinutes().toString().padStart(2, '0');
                        overtime = hours + ':' + minutes;
                    }
                }

                // Ensure format is HH:MM
                if(!/^\d{2}:\d{2}$/.test(overtime)) {
                    // Try to parse different formats
                    let parts = overtime.split(':');
                    if(parts.length === 2) {
                        overtime = parts[0].padStart(2, '0') + ':' + parts[1].padStart(2, '0');
                    } else {
                        overtime = '00:00';
                    }
                }

                let selectedDays = [];
                $(this).find('.overtime-day-checkbox:checked').each(function() {
                    selectedDays.push($(this).data('date'));
                });

                // Save overtime data - save even if overtime is 00:00 as long as days are selected
                // Or save if overtime is set
                if(overtime !== '00:00' || selectedDays.length > 0) {
                    employeeOvertimeData[empId] = {
                        overtime: overtime,
                        days: selectedDays
                    };
                }
            }
        });

        // Remove overtime data for employees that are no longer selected
        for(let empId in employeeOvertimeData) {
            if(!selectedEmployees.includes(String(empId))) {
                delete employeeOvertimeData[empId];
            }
        }

        // Update summary
        updateOvertimeSummary();

        // Recalculate totals after saving
        calculateAllTotals();

        $('#addOvertimeModal').modal('hide');

        // Show success message
        toastr.success("Overtime data saved successfully", "Success", {
            positionClass: 'toast-bottom-right'
        });
    });

    // Update overtime summary display
    function updateOvertimeSummary() {
        let summary = '';
        if (Object.keys(employeeOvertimeData).length > 0) {
            summary = '<div class="alert alert-info"><strong>Overtime Assigned:</strong><ul class="mb-0">';
            for(let empId in employeeOvertimeData) {
                let empName = $('#Employee option[value="' + empId + '"]').text();
                let overtimeInfo = employeeOvertimeData[empId];
                let daysText = '';
                if(overtimeInfo.days && overtimeInfo.days.length > 0) {
                    daysText = ' (Days: ' + overtimeInfo.days.length + ')';
                }
                summary += '<li>' + empName + ': ' + overtimeInfo.overtime + daysText + '</li>';
            }
            summary += '</ul></div>';
        }
        $('#overtimeSummary').html(summary);
    }
    // Removed - overtime is now handled in modal
    $(document).on("change", "#ShiftOverTime", function() {
        let overtime = $(this).val(); // Get the overtime time (HH:MM)
        let DayWiseTotalHours=$(this).attr('data-DayWiseTotalHours');
        calculateTotalTime(overtime,DayWiseTotalHours,flag="Modal");
    });
    $(document).on("change", "#Shiftpopup", function() {
        let overtime = "00:00";

        let DayWiseTotalHours= $("#Shiftpopup").find(":selected").data('totalhrs') || "00:00";
        let flag="Modal";
        calculateTotalTime(overtime,DayWiseTotalHours,flag);
    });
    $(document).on("change", "#Shift", function() {
        calculateAllTotals();
    });

    // Track which employees' leave info has been loaded
    let loadedEmployeeLeaves = {};

    $(document).on("change", "#Employee", function() {
        // Clear overtime data when employees change
        employeeOvertimeData = {};
        updateOvertimeSummary();
        calculateAllTotals();

        // Update employee info display
        let selectedIds = $(this).val() || [];
        let currentLoadedIds = Object.keys(loadedEmployeeLeaves).map(id => String(id));

        // Remove leave info for deselected employees
        currentLoadedIds.forEach(function(empId) {
            if(!selectedIds.includes(empId)) {
                $(".createduty-Append").find('[data-emp-leave-id="' + empId + '"]').remove();
                delete loadedEmployeeLeaves[empId];
            }
        });

        // Load leave info for newly selected employees
        if(selectedIds.length > 0) {
            selectedIds.forEach(function(empId) {
                // Only load if not already loaded
                if(!loadedEmployeeLeaves[empId]) {
                    $.ajax({
                        url: "<?php echo e(route('resort.timeandattendance.DutyRosterandLeave')); ?>",
                        type: "POST",
                        data: {"_token":"<?php echo e(csrf_token()); ?>","id":empId},
                        success: function (response) {
                            if (response.success) {
                                // Wrap the response in a div with employee ID for identification
                                let leaveBlock = $('<div data-emp-leave-id="' + empId + '"></div>').html(response.view);
                                $(".createduty-Append").append(leaveBlock);
                                loadedEmployeeLeaves[empId] = true;
                            }
                        },
                        error: function() {
                            console.error('Error loading leave info for employee:', empId);
                        }
                    });
                }
            });
        } else {
            $(".createduty-Append").html('');
            loadedEmployeeLeaves = {};
        }
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
    // Employee change handler is now in the earlier section
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
    // Calculate all totals: shift hours, overtime, day off deduction, and final total
    function calculateAllTotals() {
        try {
            let selectedEmployees = $('#Employee').val() || [];
            let employeeCount = selectedEmployees.length;

            if(employeeCount === 0) {
                $('#TotalHours').html('0');
                $('#OvertimeTotalHours').html('0');
                $('#DayOffDeduction').html('0');
                $('#FinalTotalHours').html('0');
                $('#TotalHoursInput').val('');
                $('#FinalTotalHoursInput').val('');
                return;
            }

            // Get shift hours from shift settings - use TotalHours field
            let selectedShift = $("#Shift").find(":selected");
            let shiftTotalHrs = selectedShift.data('totalhrs') || "";

            // If TotalHours is not available or empty, calculate from StartTime and EndTime
            if(!shiftTotalHrs || shiftTotalHrs === "" || shiftTotalHrs === "00:00") {
                let startTime = selectedShift.data('starttime') || "";
                let endTime = selectedShift.data('endtime') || "";

                if(startTime && endTime) {
                    // Calculate hours from StartTime and EndTime
                    let start = new Date('2000-01-01 ' + startTime);
                    let end = new Date('2000-01-01 ' + endTime);

                    // If end is before start, it's a next-day shift
                    if(end < start) {
                        end.setDate(end.getDate() + 1);
                    }

                    let diffMs = end - start;
                    let diffHours = Math.floor(diffMs / (1000 * 60 * 60));
                    let diffMinutes = Math.floor((diffMs % (1000 * 60 * 60)) / (1000 * 60));

                    shiftTotalHrs = diffHours.toString().padStart(2, '0') + ':' + diffMinutes.toString().padStart(2, '0');
                } else {
                    shiftTotalHrs = "00:00";
                }
            }

            let [shiftHours, shiftMinutes] = shiftTotalHrs.split(':');
            shiftHours = parseInt(shiftHours) || 0;
            shiftMinutes = parseInt(shiftMinutes) || 0;

            // Get date range
            let hiddenInput = $("#hiddenInput").val();
            if(!hiddenInput) {
                $('#TotalHours').html('0');
                $('#OvertimeTotalHours').html('0');
                $('#DayOffDeduction').html('0');
                $('#FinalTotalHours').html('0');
                return;
            }

            let hiddenInputArray = hiddenInput.split(' - ');
            let startDateStr = hiddenInputArray[0].trim();
            let endDateStr = hiddenInputArray[1].trim();

            // Parse dates - format is m/d/Y (e.g., "01/09/2024")
            let startParts = startDateStr.split('/');
            let endParts = endDateStr.split('/');
            // Create dates: new Date(year, monthIndex, day) - monthIndex is 0-based
            let startDate = new Date(parseInt(startParts[2]), parseInt(startParts[0]) - 1, parseInt(startParts[1]));
            let endDate = new Date(parseInt(endParts[2]), parseInt(endParts[0]) - 1, parseInt(endParts[1]));

            // Calculate number of days
            let daysDiff = Math.ceil((endDate - startDate) / (1000 * 60 * 60 * 24)) + 1;

            // Get selected day off dates - with better error handling
            let dayOffDates = [];
            console.log('=== Retrieving Day Off Dates ===');
            console.log('dayOffPicker exists:', !!dayOffPicker);
            if(dayOffPicker) {
                console.log('dayOffPicker.selectedDates:', dayOffPicker.selectedDates);
                console.log('selectedDates length:', dayOffPicker.selectedDates ? dayOffPicker.selectedDates.length : 'undefined');
            }
            console.log('Input field value:', $('#DayOffDates').val());

            try {
                // Try to get dates from flatpickr
                if(dayOffPicker && typeof dayOffPicker.selectedDates !== 'undefined' && dayOffPicker.selectedDates.length > 0) {
                    dayOffDates = dayOffPicker.selectedDates.map(date => {
                        return date.toISOString().split('T')[0];
                    });
                    console.log('Got dates from flatpickr selectedDates:', dayOffDates);
                }
                // Fallback: try to get from input value
                else if($('#DayOffDates').val()) {
                    let dayOffValue = $('#DayOffDates').val();
                    if(dayOffValue) {
                        dayOffDates = dayOffValue.split(',').map(d => d.trim()).filter(d => d);
                        console.log('Got dates from input value:', dayOffDates);
                    }
                } else {
                    console.log('No day off dates found - picker may not be initialized or no dates selected');
                }
            } catch(e) {
                console.error('Error getting day off dates:', e);
                // If there's an error, just use empty array
                dayOffDates = [];
            }

            console.log('Final day off dates array:', dayOffDates);
            console.log('Day off dates count:', dayOffDates.length);
            console.log('Employee count:', employeeCount, 'Days in range:', daysDiff);

            // Calculate GROSS shift total hours for all employees and all days (BEFORE deduction)
            let grossShiftTotalMinutes = (shiftHours * 60 + shiftMinutes) * employeeCount * daysDiff;

            // Calculate day off hours deduction
            let dayOffCount = dayOffDates.length;
            let dayOffDeductionMinutes = (shiftHours * 60 + shiftMinutes) * employeeCount * dayOffCount;

            // Calculate NET shift hours (AFTER day off deduction)
            let netShiftTotalMinutes = grossShiftTotalMinutes - dayOffDeductionMinutes;

            console.log('Gross shift minutes:', grossShiftTotalMinutes);
            console.log('Day off count:', dayOffCount, 'Deduction minutes:', dayOffDeductionMinutes);
            console.log('Net shift minutes:', netShiftTotalMinutes);

            // Calculate overtime total - only for selected days per employee
            let overtimeTotalMinutes = 0;
            for(let empId in employeeOvertimeData) {
                let overtimeInfo = employeeOvertimeData[empId];
                if(!overtimeInfo || !overtimeInfo.overtime) continue;

                let overtime = overtimeInfo.overtime || '00:00';
                let [otHours, otMinutes] = overtime.split(':');
                otHours = parseInt(otHours) || 0;
                otMinutes = parseInt(otMinutes) || 0;

                // Count only the days selected for this employee's overtime
                let overtimeDays = overtimeInfo.days || [];
                let overtimeDaysCount = overtimeDays.length;

                // Subtract overtime days that fall on day off dates
                if(dayOffDates.length > 0) {
                    overtimeDays.forEach(function(dateStr) {
                        if(dayOffDates.includes(dateStr)) {
                            overtimeDaysCount--;
                        }
                    });
                }

                overtimeTotalMinutes += (otHours * 60 + otMinutes) * overtimeDaysCount;
            }

            // Calculate final total: Net Shift Hours + Overtime Hours
            let finalTotalMinutes = netShiftTotalMinutes + overtimeTotalMinutes;

            // Format and display
            let shiftTotal = formatTimeFromMinutes(netShiftTotalMinutes); // Show NET hours after deduction
            let overtimeTotal = formatTimeFromMinutes(overtimeTotalMinutes);
            let dayOffDeduction = formatTimeFromMinutes(dayOffDeductionMinutes);
            let finalTotal = formatTimeFromMinutes(finalTotalMinutes);

            $('#TotalHours').html(shiftTotal);
            $('#TotalHoursInput').val(shiftTotal);
            $('#OvertimeTotalHours').html(overtimeTotal);
            $('#DayOffDeduction').html(dayOffDeduction);
            $('#FinalTotalHours').html(finalTotal);
            $('#FinalTotalHoursInput').val(finalTotal);

            console.log('Calculation complete:', {
                grossShiftTotal: formatTimeFromMinutes(grossShiftTotalMinutes),
                dayOffDeduction,
                netShiftTotal: shiftTotal,
                overtimeTotal,
                finalTotal
            });
        } catch(error) {
            console.error('Error in calculateAllTotals:', error);
            // Show user-friendly error
            toastr.error('Error calculating hours. Please try again.', 'Calculation Error', {
                positionClass: 'toast-bottom-right'
            });
        }
    }

    // Helper function to format minutes to HH:MM
    function formatTimeFromMinutes(totalMinutes) {
        if(totalMinutes < 0) totalMinutes = 0;
        let hours = Math.floor(totalMinutes / 60);
        let minutes = totalMinutes % 60;
        return `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}`;
    }

    // Keep the old function for modal (edit duty roster)
    function calculateTotalTime(overtime,DayWiseTotalHours,flag="")
    {
        if(overtime == "" || overtime==0)
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
            // Use provided DayWiseTotalHours (from DB or selected shift)
            totalHrs = DayWiseTotalHours;
        }
        else
        {
            // Fallback: read TotalHours from the correct shift dropdown
            if (flag === "Modal") {
                totalHrs = $("#Shiftpopup").find(":selected").data('totalhrs') || "00:00";
            } else {
                totalHrs = $("#Shift").find(":selected").data('totalhrs') || "00:00";
            }
        }

        // Ensure totalHrs is in HH:MM format (normalize cases like "8" to "08:00")
        if (!/^([0-9]{1,2}):([0-9]{2})$/.test(totalHrs)) {
            let numericHours = parseInt(totalHrs) || 0;
            totalHrs = numericHours.toString().padStart(2, '0') + ':00';
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
            // Round totalMinutes to avoid floating-point precision issues
            totalMinutes = Math.round(totalMinutes);

            if (totalMinutes >= 60) {
                totalHours += Math.floor(totalMinutes / 60);
                totalMinutes = totalMinutes % 60; // Remaining minutes after converting to hours
            }
            console.log(totalHours, totalMinutes);
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

    $(document).on('keyup', '.search', function() {
        updateFilterWiseTable();
    });
    $(document).on('change', '#Position', function() {
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

    function updateFilterWiseTable()
    {
        var search = $(".search").val();
        var Position = $("#Position").val();
        var DatePickerFilter = $("#DutyRosterCreateDatePickerFilter").val();
        let monthly  = $(".btn-monthly").hasClass("active");
        let sendclass='';
        if(monthly == true)
        {
            sendclass='Monthly';
        }
        else{
            sendclass ="Weekly";
        }
        $.ajax({
                url: "<?php echo e(route('resort.timeandattendance.DutyRosterSearch')); ?>",
                type: "POST",
                data: {"_token":"<?php echo e(csrf_token()); ?>","search":search,"Position":Position,"date":DatePickerFilter,"monthly":monthly,"sendclass":sendclass},
                success: function (response) {

                    if (response.success)
                    {

                        $(".appendData").html(response.view);

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


    }

    $('#clearFilter').on('click', function() {
        $('.search').val('');
        $('#Position').val('').trigger('change');
        $('#DutyRosterCreateDatePickerFilter').val('');
        updateFilterWiseTable();
    });
</script>
<?php $__env->stopSection(); ?>


<?php echo $__env->make('resorts.layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /workspaces/HRVMS-Wisdom-AI/resources/views/resorts/timeandattendance/dutyroster/CreateDutyRoster.blade.php ENDPATH**/ ?>