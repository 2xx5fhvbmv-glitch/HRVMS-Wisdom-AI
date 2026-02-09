<div class="weekly-main <?php if($sendclass =="Weekly"): ?> d-block <?php else: ?> d-none <?php endif; ?>" >
    <div class="attendance-bg">
        <div class="row justify-content-center gx-md-4 g-2">
            <div class="col-auto">
                <div class="doughnut-label">
                    <span class="bg-themeSuccess"></span>On Time
                </div>
            </div>
            <div class="col-auto">
                <div class="doughnut-label">
                    <span class="bg-themePurple"></span>Late
                </div>
            </div>
            <div class="col-auto">
                <div class="doughnut-label">
                    <span class="bg-themeDanger"></span>Missing
                </div>
            </div>
            <div class="col-auto">
                <div class="doughnut-label">
                    <span class="bg-themeWarning"></span>DayOff
                </div>
            </div>
        </div>
    </div>
    <div class="table-responsive table-arrowAtten mb-4">
        
        <table class="table table-collapseNew table-attendance   mb-1">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Employee Name</th>
                    <?php $__currentLoopData = $headers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $d): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php
                            $currentDate = isset($d['full_date']) ? $d['full_date']->format('Y-m-d') : date('Y-m-d', strtotime($d['date']));
                            $isPublicHoliday = isset($publicHolidays) && in_array($currentDate, $publicHolidays);
                        ?>
                        <th class="<?php echo e($isPublicHoliday ? 'public-holiday-header' : ''); ?>"><?php echo e($d['date']); ?> <span><?php echo e($d['day']); ?></span></th>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    <th>Worked <br>Hours</th>
                    <th>Balance <br>Hours</th>
                    <th>OT <br>Hours</th>
                    <th>Leave</th>

                </tr>
            </thead>
            <tbody>

                <?php if($attandanceregister->isNotEmpty()): ?>
                    <?php $__currentLoopData = $attandanceregister; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $a): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <tr>
                            <td><?php echo e($a->EmployeeId); ?></td>
                            <td>
                                <div class="tableUser-block">
                                    <div class="img-circle"><img src="<?php echo e($a->profileImg); ?>" alt="user">
                                    </div>
                                    <span><?php echo e($a->EmployeeName); ?></span>
                                </div>
                            </td>
                            <?php

                                $RosterInternalData = Common::GetAttandanceRegister($resort_id, $a->duty_roster_id, $a->emp_id, $WeekstartDate, $WeekendDate,$startOfMonth,$endOfMonth,"weekly");
                                $totalHours = 0;
                                $TotalweeklyOt=0;
                                $TotalweeklyLeave=0;
                                $dataCount = $RosterInternalData->count();
                                $minColumns = 7; // Minimum number of columns to account for all days of the week
                            ?>
                                <?php $__currentLoopData = $headers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $header): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php
                                    // Get shift data for the current header date
                                    $currentDate = isset($header['newdate']) ? date('Y-m-d', strtotime(str_replace('-', '/', $header['newdate']))) : date('Y-m-d', strtotime($header['date']));
                                    $isPublicHoliday = isset($publicHolidays) && in_array($currentDate, $publicHolidays);
                                    $shiftData = $RosterInternalData->firstWhere('date', $currentDate);
                                    $toatalHoursForDay = 0;


                                    if (isset($shiftData) && $shiftData->Status == "Present") {
                                        list($hours, $minutes) = explode(':', $shiftData->DayWiseTotalHours ?? '0:0');


                                        $toatalHoursForDay = (int)$hours + ((int)$minutes / 60);


                                        $totalHours += $toatalHoursForDay;

                                        list($Othours, $Otminutes) = explode(':', $shiftData->OverTime ?? '0:0');
                                        $TotalweeklyOt +=  (int)$Othours + ((int)$Otminutes / 60);

                                        }


                                ?>

                                <td class="<?php echo e($isPublicHoliday ? 'public-holiday-cell' : ''); ?> <?php if(isset($shiftData) && $shiftData->Status == "DayOff" ): ?> Register-col-leave <?php endif; ?>">
                                        <?php if(isset($shiftData) && $shiftData->Status == "Present" ): ?>
                                            <?php if(isset($shiftData->CheckingTime)): ?>

                                                <div
                                                    <?php if(isset($shiftData->InternalStatus) &&  $shiftData->InternalStatus =="OnTime" ||$shiftData->InternalStatus =="Early"): ?>
                                                        class="badge badge-themeSuccess"
                                                        <?php $icon= 'rigth-success.svg'; ?>
                                                    <?php else: ?>
                                                    class="badge badge-themePurple"

                                                    <?php $icon= 'rigth-purple.svg'; ?>
                                                    <?php endif; ?>
                                                    >
                                                    <img src="<?php echo e(URL::asset('resorts_assets/images/'.$icon)); ?>" alt="icon" class="left">
                                                    <?php echo e($shiftData->CheckingTime); ?>

                                                </div>
                                            <?php endif; ?>
                                            <br>

                                            <?php if($shiftData->CheckingOutTime): ?>
                                                <div class="badge badge-themePurple">
                                                    <img src="<?php echo e(URL::asset('resorts_assets/images/rigth-purple.svg')); ?>" alt="">
                                                    <?php echo e($shiftData->CheckingOutTime); ?>

                                                </div>
                                            <?php else: ?>

                                            <?php if($shiftData->msg =="PleaseCheckout"): ?>
                                            <div class="badge badge-themeDanger tt-hover tt-large"><img src="<?php echo e(URL::asset('resorts_assets/images/right-danger.svg')); ?>" alt="">--:--
                                                    <span class="tt-main"><span class="tt-inner">
                                                        <p><b>Missing CheckOut</b>The Employee has not Checked Out at the
                                                            expected time. would you like to assign them overtime?
                                                            <?php echo e($shiftData->differenceInHours); ?>

                                                        </p>
                                                        
                                                            <a href="javascript:void(0)" class="btn btn-themeSkyblue CheckOutModel"
                                                                    data-attendance_id="<?php echo e($shiftData->Attd_id); ?>"
                                                                    data-shiftname="<?php echo e($shiftData->ShiftName); ?>"
                                                                    data-todoshiftstime="<?php echo e($shiftData->StartTime); ?>"
                                                                    data-todoshiftetime="<?php echo e($shiftData->EndTime); ?>"
                                                                    data-todoshiftstimeShow="<?php echo e($shiftData->StartTimeShow); ?>"
                                                                    data-todoshiftetimeShow="<?php echo e($shiftData->EndTimeShow); ?>"
                                                                    data-todoassignedot="<?php echo e(($shiftData->OverTime ?? '-')); ?>"
                                                                    data-totalExtraHours="<?php echo e($shiftData->differenceInHours); ?>"
                                                                    data-OTStatus="<?php echo e($shiftData->OTStatus); ?>"
                                                                    data-CheckingOutTime="<?php echo e($shiftData->CheckingOutTime); ?>"
                                                            >Enter Checkout Time
                                                        </a>
                                                    </span></span>
                                            </div>
                                            <?php endif; ?>


                                        <?php endif; ?>

                                        <div class="btn-block">
                                            <a href="javascript:void(0)" class="btn-lg-icon icon-bg-blue LocationHistoryData" data-location="<?php echo e($shiftData->InTime_Location); ?>"><i class="fa-regular fa-location-dot"></i></a>

                                            <?php if(isset($shiftData->ApprovedName) && $shiftData->ApprovedName != ""): ?>
                                            <a href="javascript:void(0)" class="btn-lg-icon icon-bg-skyblue tt-hover"><i class="fa-regular fa-comment "></i>
                                                <span class="tt-main"><span class="tt-inner">OT Approved by
                                                        <?php echo e($shiftData->ApprovedName); ?></span></span></a>
                                            <?php endif; ?>
                                        </div>
                                        <?php if($shiftData->OverTime && $shiftData->OverTime != "-" && $shiftData->OTStatus == null && $shiftData->OTstatus !="Rejacted"): ?>
                                            <p>
                                                +<?php echo e($shiftData->OverTime); ?><span class="badge badge-themeWarning">OT</span>
                                            </p>
                                        <?php endif; ?>


                                    <?php elseif(isset($shiftData) && $shiftData->Status == "Absent"): ?>
                                        <b class="text-skyblue" >Absent</b>
                                        <?php
                                            $TotalweeklyLeave = $TotalweeklyLeave+1;
                                        ?>
                                    <?php elseif(isset($shiftData) && $shiftData->Status == "DayOff" ): ?>

                                        <i class="fa-solid fa-xmark"></i>
                                    <?php else: ?>

                                    -
                                    <?php endif; ?>

                                </td>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                            <?php

                            $toatalweeklyHoursForDay = $totalHours - floor($TotalweeklyOt);
                        ?>
                                <td><?php echo e($toatalweeklyHoursForDay); ?></td>
                                <td><?php echo e(48-$toatalweeklyHoursForDay); ?></td>
                                <td> <?php echo e($TotalweeklyOt); ?> </td>
                                <td>
                                    <?php if($TotalweeklyLeave != 0): ?>
                                    <span class="ttb-hover">
                                        <?php echo e($TotalweeklyLeave); ?>

                                        <?php if($LeaveCategory->isNotEmpty()): ?>
                                            <?php $__currentLoopData = $LeaveCategory; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k => $leave): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <?php
                                                    $matchedLeaveType = null;
                                                    $matchedFromDate = null;
                                                    $matchedToDate = null;

                                                    // Check if there's a matching leave in RosterInternalDataMonth
                                                    $weeklyleaveMatched = $RosterInternalData->first(function ($shiftData) use ($leave, $k) {
                                                        return isset($shiftData->LeaveData[$k]) && $shiftData->LeaveData[$k]['leave_cat_id'] == $leave->id;
                                                    });

                                                    if ($weeklyleaveMatched) {
                                                        $weeklyleaveMatched = $weeklyleaveMatched->LeaveData[$k];
                                                        $matchedLeaveType = $weeklyleaveMatched['leave_type'] ?? null;
                                                        $matchedFromDate = $weeklyleaveMatched['from_date'] ?? null;
                                                        $matchedToDate = $weeklyleaveMatched['to_date'] ?? null;
                                                    }
                                                ?>
                                         <?php if($weeklyleaveMatched): ?>
                                                    <span class="ttb-main">
                                                        <span class="ttb-inner">
                                                            <span>
                                                                <span><?php echo e(\Carbon\Carbon::parse($matchedFromDate)->format('d/m/Y')); ?> - <?php echo e(\Carbon\Carbon::parse($matchedToDate)->format('d/m/Y')); ?></span>
                                                                <p><?php echo e($matchedLeaveType); ?></p>
                                                            </span>
                                                        </span>
                                                    </span>
                                                <?php endif; ?>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        <?php endif; ?>
                                    </span>
                                <?php else: ?>
                                    0
                                    <span class="ttb-main">
                                        <span class="ttb-inner">
                                            <span>
                                                <p>No Leave Found..</p>
                                            </span>
                                        </span>
                                    </span>
                                <?php endif; ?>

                                </td>


                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                <?php endif; ?>

            </tbody>
        </table>
    </div>
    <div class="pagination-custom">
        <nav aria-label="Page navigation example">
            <?php echo $attandanceregister->appends(['view' => request('view', 'weekly')])->links('pagination::bootstrap-4'); ?>


        </nav>
    </div>
</div>
<div class="monthly-main  <?php if($sendclass !="Weekly"): ?> d-block <?php else: ?> d-none <?php endif; ?>"">
    <div class="attendance-bg">
        <div class="row justify-content-center gx-md-4 g-2">
            <div class="col-auto">
                <div class="doughnut-label">
                    <span class="bg-themeSuccess"></span>On Time
                </div>
            </div>
            <div class="col-auto">
                <div class="doughnut-label">
                    <span class="bg-themePurple"></span>Late
                </div>
            </div>
            <div class="col-auto">
                <div class="doughnut-label">
                    <span class="bg-themeDanger"></span>Missingd
                </div>
            </div>
            <div class="col-auto">
                <div class="doughnut-label">
                    <span class="bg-themeWarning"></span>DayOff
                </div>
            </div>
        </div>
    </div>
    <div class="table-responsive mb-4">
        <table class="table table-collapseNew  table-attendance  mb-1">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Employee Name</th>
                    <?php if(!empty($monthwiseheaders)): ?>
                        <?php $__currentLoopData = $monthwiseheaders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $h): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php
                                $currentDate = isset($h['newdate']) ? date('Y-m-d', strtotime(str_replace('-', '/', $h['newdate']))) : (date('Y-m') . '-' . str_pad($h['day'], 2, '0', STR_PAD_LEFT));
                                $isPublicHoliday = isset($publicHolidays) && in_array($currentDate, $publicHolidays);
                            ?>
                            <th class="<?php echo e($isPublicHoliday ? 'public-holiday-header' : ''); ?>"><?php echo e($h['day']); ?> <span><?php echo e($h['dayname']); ?></span></th>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    <?php endif; ?>
                    <th>Worked <br>Hours</th>
                    <th>Balance <br>Hours</th>
                    <th>OT <br>Hours</th>
                    <th>Leave</th>
                </tr>
            </thead>
            <tbody>
                <?php if($attandanceregister->isNotEmpty()): ?>
                <?php $__currentLoopData = $attandanceregister; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $a): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr>
                        <td><?php echo e($a->EmployeeId); ?></td>
                        <td>
                            <div class="tableUser-block">
                                <div class="img-circle">
                                    <img src="<?php echo e($a->profileImg); ?>" alt="user">
                                </div>
                                <span><?php echo e($a->EmployeeName); ?></span>
                            </div>
                        </td>
                        <?php
                            $RosterInternalDataMonth = Common::GetAttandanceRegister($resort_id, $a->duty_roster_id, $a->emp_id, $WeekstartDate, $WeekendDate,$startOfMonth,$endOfMonth, "Monthwise");
                            $totalHours = 0;
                            $TotalMonthlyOt  = 0;
                            $TotalMonthlyLeave = 0;
                            $TotalMonltyDayOff=0;

                        ?>

                        <?php $__currentLoopData = $monthwiseheaders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $h): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php
                                $date = date('Y-m') . '-' . str_pad($h['day'], 2, '0', STR_PAD_LEFT);
                                $isPublicHoliday = isset($publicHolidays) && in_array($date, $publicHolidays);
                                $shiftData = $RosterInternalDataMonth->firstWhere('date', $date);

                                $toatalHoursForDay = 0;

                                if ($shiftData && $shiftData->Status == "Present") {
                                    list($hours, $minutes) = explode(':', $shiftData->DayWiseTotalHours ?? '0:0');
                                    $toatalHoursForDay = (int)$hours + ((int)$minutes / 60);
                                    $totalHours += $toatalHoursForDay;

                                    list($Othours, $Otminutes) = explode(':', $shiftData->OverTime ?? '0:0');
                                    $TotalMonthlyOt += (int)$Othours + ((int)$Otminutes / 60);
                                }
                            ?>

                            <td class="<?php echo e($isPublicHoliday ? 'public-holiday-cell' : ''); ?> <?php if(isset($shiftData) && $shiftData->Status == "DayOff" ): ?> Register-col-leave <?php endif; ?>">

                                <?php if($shiftData && $shiftData->Status == "Present" ): ?>
                                    <?php if($shiftData->CheckingTime): ?>

                                        <div
                                            <?php if(isset($shiftData->InternalStatus) &&  $shiftData->InternalStatus =="OnTime" ||$shiftData->InternalStatus =="Early"): ?>
                                                class="badge badge-themeSuccess"
                                                <?php $icon= 'rigth-success.svg'; ?>
                                            <?php else: ?>
                                            class="badge badge-themePurple"

                                            <?php $icon= 'rigth-purple.svg'; ?>
                                            <?php endif; ?>
                                            >
                                            <img src="<?php echo e(URL::asset('resorts_assets/images/'.$icon)); ?>" alt="icon" class="left">
                                            <?php echo e($shiftData->CheckingTime); ?>

                                        </div>
                                    <?php endif; ?>
                                    <br>

                                    <?php if($shiftData->CheckingOutTime): ?>
                                        <div class="badge badge-themePurple">
                                            <img src="<?php echo e(URL::asset('resorts_assets/images/rigth-purple.svg')); ?>" alt="">
                                            <?php echo e($shiftData->CheckingOutTime); ?>

                                        </div>
                                    <?php else: ?>

                                    <?php if($shiftData->msg =="PleaseCheckout"): ?>
                                    <div class="badge badge-themeDanger tt-hover tt-large"><img src="<?php echo e(URL::asset('resorts_assets/images/right-danger.svg')); ?>" alt="">--:--
                                            <span class="tt-main"><span class="tt-inner">
                                                <p><b>Missing CheckOut</b>The Employee has not Checked Out at the
                                                    expected time. would you like to assign them overtime?
                                                    <?php echo e($shiftData->differenceInHours); ?>

                                                </p>
                                                
                                                    <a href="javascript:void(0)" class="btn btn-themeSkyblue CheckOutModel"
                                                            data-attendance_id="<?php echo e($shiftData->Attd_id); ?>"
                                                            data-shiftname="<?php echo e($shiftData->ShiftName); ?>"
                                                            data-todoshiftstime="<?php echo e($shiftData->StartTime); ?>"
                                                            data-todoshiftetime="<?php echo e($shiftData->EndTime); ?>"
                                                            data-todoshiftstimeShow="<?php echo e($shiftData->StartTimeShow); ?>"
                                                            data-todoshiftetimeShow="<?php echo e($shiftData->EndTimeShow); ?>"
                                                            data-todoassignedot="<?php echo e(($shiftData->OverTime ?? '-')); ?>"
                                                            data-totalExtraHours="<?php echo e($shiftData->differenceInHours); ?>"
                                                            data-OTStatus="<?php echo e($shiftData->OTStatus); ?>"
                                                            data-CheckingOutTime="<?php echo e($shiftData->CheckingOutTime); ?>"
                                                    >Enter Checkout Time
                                                </a>
                                            </span></span>
                                    </div>
                                    <?php endif; ?>


                                <?php endif; ?>

                                <div class="btn-block">
                                    <a href="javascript:void(0)" class="btn-lg-icon icon-bg-blue LocationHistoryData" data-location="<?php echo e($shiftData->InTime_Location); ?>"><i class="fa-regular fa-location-dot"></i></a>

                                    <?php if(isset($shiftData->ApprovedName) && $shiftData->ApprovedName != ""): ?>
                                    <a href="javascript:void(0)" class="btn-lg-icon icon-bg-skyblue tt-hover"><i class="fa-regular fa-comment "></i>
                                        <span class="tt-main"><span class="tt-inner">OT Approved by
                                                <?php echo e($shiftData->ApprovedName); ?></span></span></a>
                                    <?php endif; ?>
                                </div>
                                <?php if($shiftData->OverTime && $shiftData->OverTime != "-" && $shiftData->OTStatus == null && $shiftData->OTstatus !="Rejacted"): ?>
                                    <p>
                                        +<?php echo e($shiftData->OverTime); ?><span class="badge badge-themeWarning">OT</span>
                                    </p>
                                <?php endif; ?>


                            <?php elseif(isset($shiftData)  && $shiftData->Status !="" && $shiftData->Status == "Absent"): ?>
                                <b class="text-skyblue" > On Leave</b>
                                <?php
                                   $TotalMonthlyLeave += 1;
                                ?>
                            <?php elseif(isset($shiftData) && $shiftData->Status == "DayOff" ): ?>
                                <?php
                                    $TotalMonltyDayOff += 1;
                                ?>
                            <i class="fa-solid fa-xmark"></i>
                            <?php else: ?>

                            -
                            <?php endif; ?>

                            </td>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>


                        <?php

                        $TotalMonthlyOt = (float) $TotalMonthlyOt;  // Cast to float

                        $totalHours = (float) $totalHours;  // Cast to float

                        $TotalMonthlyOtFormatted = sprintf('%d:%02d', floor($TotalMonthlyOt), round(($TotalMonthlyOt - floor($TotalMonthlyOt)) * 60));

                        $toatalMonthHoursForDay = $totalHours - floor($TotalMonthlyOt);  // This will result in a float value
                            $monthdays = count($monthwiseheaders) - $TotalMonltyDayOff;
                            $expectedHours = $monthdays * 8;
                            $balanceHours = $expectedHours - $totalHours;
                        ?>

                        <td><?php echo e($toatalMonthHoursForDay); ?></td>
                        <td><?php echo e($balanceHours); ?></td>
                        <td><?php echo e($TotalMonthlyOt); ?></td>

                        <td>

                        <?php if($TotalMonthlyLeave !=0): ?>
                        <span class="ttb-hover">
                            <?php echo e($TotalMonthlyLeave); ?>

                            <?php if($LeaveCategory->isNotEmpty()): ?>
                                <?php $__currentLoopData = $LeaveCategory; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k => $leave): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <?php
                                        $matchedLeaveType = null;
                                        $matchedFromDate = null;
                                        $matchedToDate = null;

                                        // Check if there's a matching leave in RosterInternalDataMonth
                                        $leaveMatched = $RosterInternalDataMonth->first(function ($shiftData) use ($leave, $k) {
                                            return isset($shiftData->LeaveData[$k]) && $shiftData->LeaveData[$k]['leave_cat_id'] == $leave->id;
                                        });

                                        if ($leaveMatched) {
                                            // Extract data from matched leave
                                            $matchedLeaveData = $leaveMatched->LeaveData[$k];
                                            $matchedLeaveType = $matchedLeaveData['leave_type'] ?? null;
                                            $matchedFromDate = $matchedLeaveData['from_date'] ?? null;
                                            $matchedToDate = $matchedLeaveData['to_date'] ?? null;
                                        }
                                    ?>

                                    <?php if($leaveMatched): ?>

                                            <span class="ttb-main">
                                                <span class="ttb-inner">
                                                    <span>
                                                        <span><?php echo e(\Carbon\Carbon::parse($matchedFromDate)->format('d/m/Y')); ?> - <?php echo e(\Carbon\Carbon::parse($matchedToDate)->format('d/m/Y')); ?></span>
                                                        <p><?php echo e($matchedLeaveType); ?></p>
                                                    </span>

                                                </span>
                                            </span>

                                    <?php endif; ?>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            <?php endif; ?>

                        </span>
                        <?php else: ?>
                           0
                            <span class="ttb-main">

                                <span class="ttb-inner">
                                    <span>
                                        <p>No Leave Found..</p>
                                    </span>

                                </span>
                            </span>
                        <?php endif; ?>
                    </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            <?php endif; ?>

            </tbody>
        </table>
    </div>
    <div class="pagination-custom">
        <nav aria-label="Page navigation example">
            <?php echo $attandanceregister->appends(['view' => request('view', 'monthly')])->links('pagination::bootstrap-4'); ?>

        </nav>
    </div>
</div>
<?php /**PATH /workspaces/HRVMS-Wisdom-AI/resources/views/resorts/renderfiles/ResigterRosterSearch.blade.php ENDPATH**/ ?>