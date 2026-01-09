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
            <div class="card">
                    <div class="appendData">
                            <div class="monthly-main">
                                
                                <div class="viewBudget-accordion" id="accordionDutyRoster">
                                    <?php if(!empty($groupedRosterData)): ?>
                                        <?php $deptIteration = 1; ?>
                                        <?php $__currentLoopData = $groupedRosterData; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $deptId => $deptData): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            
                                            <div class="accordion-item mb-2 department-accordion">
                                                <h2 class="accordion-header" id="headingDept<?php echo e($deptIteration); ?>">
                                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                                            data-bs-target="#collapseDept<?php echo e($deptIteration); ?>" aria-expanded="false"
                                                            aria-controls="collapseDept<?php echo e($deptIteration); ?>">
                                                        <i class="fas fa-building me-2"></i>
                                                        <h3><?php echo e($deptData['dept_name']); ?></h3>
                                                        <span class="badge badge-dark ms-2 small">
                                                            Employees: <?php echo e(count($deptData['employees']) + array_sum(array_map(function($section) { return count($section['employees']); }, $deptData['sections']))); ?>

                                                        </span>
                                                    </button>
                                                </h2>
                                                <div id="collapseDept<?php echo e($deptIteration); ?>" class="collapse"
                                                     aria-labelledby="headingDept<?php echo e($deptIteration); ?>" data-bs-parent="#accordionDutyRoster">
                                                    <div class="accordion-body p-2">
                                                        <?php $sectionIteration = 1; ?>
                                                        
                                                        <?php if(!empty($deptData['sections'])): ?>
                                                            <?php $__currentLoopData = $deptData['sections']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sectionId => $sectionData): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                                
                                                                <div class="accordion mb-2 ms-3 section-accordion" id="accordionSec<?php echo e($deptIteration); ?>_<?php echo e($sectionIteration); ?>">
                                                                    <div class="accordion-item">
                                                                        <h2 class="accordion-header" id="headingSec<?php echo e($deptIteration); ?>_<?php echo e($sectionIteration); ?>">
                                                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                                                                    data-bs-target="#collapseSec<?php echo e($deptIteration); ?>_<?php echo e($sectionIteration); ?>"
                                                                                    aria-expanded="false" aria-controls="collapseSec<?php echo e($deptIteration); ?>_<?php echo e($sectionIteration); ?>">
                                                                                <i class="fas fa-layer-group me-2"></i>
                                                                                <span><?php echo e($sectionData['section_name']); ?></span>
                                                                                <span class="badge badge-dark ms-2 small">Employees: <?php echo e(count($sectionData['employees'])); ?></span>
                                                                            </button>
                                                                        </h2>
                                                                        <div id="collapseSec<?php echo e($deptIteration); ?>_<?php echo e($sectionIteration); ?>"
                                                                             class="collapse"
                                                                             aria-labelledby="headingSec<?php echo e($deptIteration); ?>_<?php echo e($sectionIteration); ?>"
                                                                             data-bs-parent="#accordionSec<?php echo e($deptIteration); ?>_<?php echo e($sectionIteration); ?>">
                                                                            <div class="accordion-body p-2">
                                                                                
                                                                                <div class="table-responsive mb-4">
                                                                                    <table class="table table-bordered table-createDutymonthly mb-1">
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
                                                                                                <th>Summary</th>
                                                                                            </tr>
                                                                                        </thead>
                                                                                        <tbody>
                                                                                            <?php if(!empty($sectionData['employees'])): ?>
                                                                                                <?php $__currentLoopData = $sectionData['employees']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
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
                                                                                                            $RosterInternalDataMonth = Common::GetRosterdata($resort_id, $r->duty_roster_id, $r->emp_id, $WeekstartDate, $WeekendDate, $startOfMonth, $endOfMonth, "Monthwise");
                                                                                                            $totalHoursMonth = 0;
                                                                                                        ?>

                                                                                                        <?php $__currentLoopData = $monthwiseheaders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $h): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                                                                        <?php
                                                                                                            $formattedDate = \Carbon\Carbon::parse($h['date'])->format('Y-m-d');
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
                                                                                                                            <?php if($shiftData && isset($shiftData->OverTime) && $shiftData->OverTime != '00:00' && $shiftData->OverTime != '0'): ?>
                                                                                                                                <?php
                                                                                                                                    $otParts = explode(':', $shiftData->OverTime);
                                                                                                                                    $otHours = isset($otParts[0]) ? (int)$otParts[0] : 0;
                                                                                                                                    $otMinutes = isset($otParts[1]) ? (int)$otParts[1] : 0;
                                                                                                                                    $otDisplay = $otHours > 0 ? $otHours . ' hr' : '';
                                                                                                                                    if ($otMinutes > 0) {
                                                                                                                                        $otDisplay .= ($otDisplay ? ' ' : '') . $otMinutes . ' min';
                                                                                                                                    }
                                                                                                                                    $otDisplay = $otDisplay ?: '0 hr';
                                                                                                                                ?>
                                                                                                                                <p>OT: <?php echo e($otDisplay); ?></p>
                                                                                                                            <?php else: ?>
                                                                                                                                <p>OT: 0 hr</p>
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
                                                                                            <?php else: ?>
                                                                                                <tr>
                                                                                                    <td colspan="<?php echo e(count($monthwiseheaders) + 2); ?>" style="text-align: center">No Records Found..</td>
                                                                                                </tr>
                                                                                            <?php endif; ?>
                                                                                        </tbody>
                                                                                    </table>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <?php $sectionIteration++; ?>
                                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                        <?php endif; ?>

                                                        
                                                        <?php if(!empty($deptData['employees'])): ?>
                                                            <div class="table-responsive mb-4">
                                                                <table class="table table-bordered table-createDutymonthly mb-1">
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
                                                                            <th>Summary</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        <?php $__currentLoopData = $deptData['employees']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
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
                                                                                    $RosterInternalDataMonth = Common::GetRosterdata($resort_id, $r->duty_roster_id, $r->emp_id, $WeekstartDate, $WeekendDate, $startOfMonth, $endOfMonth, "Monthwise");
                                                                                    $totalHoursMonth = 0;
                                                                                ?>

                                                                                <?php $__currentLoopData = $monthwiseheaders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $h): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                                                <?php
                                                                                    $formattedDate = \Carbon\Carbon::parse($h['date'])->format('Y-m-d');
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
                                                                                                    <?php if($shiftData && isset($shiftData->OverTime) && $shiftData->OverTime != '00:00' && $shiftData->OverTime != '0'): ?>
                                                                                                        <?php
                                                                                                            $otParts = explode(':', $shiftData->OverTime);
                                                                                                            $otHours = isset($otParts[0]) ? (int)$otParts[0] : 0;
                                                                                                            $otMinutes = isset($otParts[1]) ? (int)$otParts[1] : 0;
                                                                                                            $otDisplay = $otHours > 0 ? $otHours . ' hr' : '';
                                                                                                            if ($otMinutes > 0) {
                                                                                                                $otDisplay .= ($otDisplay ? ' ' : '') . $otMinutes . ' min';
                                                                                                            }
                                                                                                            $otDisplay = $otDisplay ?: '0 hr';
                                                                                                        ?>
                                                                                                        <p>OT: <?php echo e($otDisplay); ?></p>
                                                                                                    <?php else: ?>
                                                                                                        <p>OT: 0 hr</p>
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
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php $deptIteration++; ?>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    <?php else: ?>
                                        <div class="alert alert-info">No duty roster data found.</div>
                                    <?php endif; ?>
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


    // Declare day off picker for modal at global scope so it's accessible to all functions
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

        // Initialize model picker on load
        initializeDayOffPickerModel();

        // Ensure day off picker model opens on click
        $(document).on('click', '#DayOffDatesModel', function() {
            if(dayOffPickerModel && dayOffPickerModel.isOpen !== true) {
                dayOffPickerModel.open();
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
                    dateFormat: "H:i", // 24-hour format (HH:MM)
                    time_24hr: true,  // Use 24-hour format to match database
                    minuteIncrement: 1, // Allows 1-minute steps
                });
            }

            // Convert overtime to proper format if needed
            // Handle cases where overtime might be "0", "00:00", or empty
            if (!overtime || overtime === '0' || overtime === 0) {
                overtime = '00:00';
            }

            // Ensure format is HH:MM (24-hour)
            if (overtime && overtime.indexOf(':') === -1) {
                // If it's just a number, convert to HH:MM
                let hours = parseInt(overtime) || 0;
                overtime = String(hours).padStart(2, '0') + ':00';
            }

            $("#ShiftOverTime").val(overtime);
            if ($("#ShiftOverTime")[0]._flatpickr) {
                $("#ShiftOverTime")[0]._flatpickr.setDate('2000-01-01 ' + overtime, false);
            }
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

</script>
<?php $__env->stopSection(); ?>


<?php echo $__env->make('resorts.layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\wamp64\www\Wisdom-Ai\HRVMS-Wisdom-AI\resources\views/resorts/timeandattendance/dutyroster/ViewDutyRoster.blade.php ENDPATH**/ ?>