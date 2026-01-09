<div class="weekly-main <?php if($sendclass =="Weekly"): ?> d-block <?php else: ?> d-none <?php endif; ?>" >
    <div class="table-responsive mb-4">
        <table id="createDutyWeeklyTable" class="table table-createDutyWeekly mb-1">
            <thead>
                <tr>
                    <th>Employee Name</th>
                    <?php $__currentLoopData = $headers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $d): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <th><?php echo e($d['date']); ?> <span><?php echo e($d['day']); ?></span></th>
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
                                    $shiftData = $RosterInternalData->firstWhere('date', date('Y-m-d', strtotime($header['date'])));

                                    $toatalHoursForDay = 0;
                                    if ($shiftData) {
                                        if($shiftData->Status!= 'DayOff'){
                                            $startTime = \Carbon\Carbon::parse($shiftData->StartTime);
                                            $endTime = \Carbon\Carbon::parse($shiftData->EndTime);
                                            $hours_abc = $startTime->diffInHours($endTime);
                                            // list($hours, $minutes) = explode(':', $shiftData->DayWiseTotalHours);
                                            // $toatalHoursForDay = (int)$hours + ((int)$minutes / 60); // Convert to decimal hours
                                            $toatalHoursForDay = $hours_abc;
                                            $totalHours += $toatalHoursForDay; // Update total hours worked
                                        }else{
                                            $toatalHoursForDay = 0;
                                        }
                                    }
                                ?>

                                <td>
                                    <div class="createDuty-tableBlock <?php echo e($shiftData->ShiftNameColor ?? ''); ?>">
                                        <div class="d-flex">
                                            <?php if($shiftData): ?>
                                                <div>
                                                    <p><?php echo e($startTime->format('h:i A')); ?> - <?php echo e($endTime->format('h:i A')); ?></p>
                                                    <span><?php echo e($shiftData->ShiftName); ?></span>
                                                </div>
                                                <div class="badge"><?php echo e($toatalHoursForDay); ?> <?php echo e($shiftData->color); ?> hrs</div>
                                            <?php else: ?>
                                                <div class="createDuty-empty">No Shift Assigned</div>
                                            <?php endif; ?>
                                        </div>
                                        <?php if($shiftData): ?>
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
                                        <?php endif; ?>
                                    </div>
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
<div class="monthly-main  <?php if($sendclass !="Weekly"): ?> d-block <?php else: ?> d-none <?php endif; ?>" >
    <div class="table-responsive mb-4">
        <table id="" class="table table-bordered table-createDutymonthly mb-1">


            <thead>
                <tr>
                    <th>Employee Name</th>
                    <?php if(!empty($monthwiseheaders)): ?>
                        <?php $__currentLoopData = $monthwiseheaders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $h): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <th><?php echo e($h['day']); ?> <span><?php echo e($h['dayname']); ?></span></th>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    <?php endif; ?>


                    <?php if($LeaveCategory->isNotEmpty()): ?>
                        <?php $__currentLoopData = $LeaveCategory; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $l): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <th class="col-total">
                                <span class="badge" style="background-color:<?php echo e($l->color); ?>"><?php echo e(substr($l->leave_type,0,1)); ?></span>
                            </th>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
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

                            <!-- Loop through each monthwise header for the status per day -->
                            <?php $__currentLoopData = $monthwiseheaders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $h): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php
                                // $date = date('Y-m') . '-' . str_pad($h['day'], 2, '0', STR_PAD_LEFT);

                                $formattedDate = \Carbon\Carbon::parse($h['date'])->format('Y-m-d');

                                $shiftData = $RosterInternalDataMonth->firstWhere('date', $formattedDate);


                                ?>
                                <td <?php if(isset($shiftData) && $shiftData->Status == "DayOff" ): ?> class="col-leave" <?php endif; ?>>
                                    <?php if($shiftData && $shiftData->LeaveFirstName != null): ?>
                                        <?php if($shiftData->Status == "Present"): ?>
                                            P
                                        <?php elseif($shiftData->Status == "DayOff" ): ?>

                                            <i class="fa-solid fa-xmark"></i>
                                        <?php else: ?>
                                            <span class="badge" style="background-color:<?php echo e($shiftData->LeaveColor); ?>">
                                                <?php echo e($shiftData->LeaveFirstName); ?>

                                            </span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>


                            <?php $__currentLoopData = $LeaveCategory; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $l): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>

                                <?php
                                    $leaveTypeCount = $RosterInternalDataMonth->sum(function($shiftData) use ($l) {


                                        return isset($shiftData->StatusCount[$l->leave_type]) ? $shiftData->StatusCount[$l->leave_type] : 1;
                                    });
                                ?>

                                <td>
                                    <?php echo e($leaveTypeCount); ?>

                                </td>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
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
<?php /**PATH C:\wamp64\www\Wisdom-Ai\HRVMS-Wisdom-AI\resources\views/resorts/renderfiles/DutyRosterSearch.blade.php ENDPATH**/ ?>