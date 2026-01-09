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
                                                                            <th><?php echo e($h['day']); ?> <span><?php echo e($h['dayname']); ?></span></th>
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

                                                                                <td>
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
                                                        <th><?php echo e($h['day']); ?> <span><?php echo e($h['dayname']); ?></span></th>
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

                                                        <td>
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

<?php /**PATH C:\wamp64\www\Wisdom-Ai\HRVMS-Wisdom-AI\resources\views/resorts/renderfiles/DutyRosterAccordion.blade.php ENDPATH**/ ?>