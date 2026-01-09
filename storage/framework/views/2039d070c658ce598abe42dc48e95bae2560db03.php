
<?php $__env->startSection('page_tab_title' ,"Dashboard"); ?>

<?php if($message = Session::get('success')): ?>
    <div class="alert alert-success">
        <p><?php echo e($message); ?></p>
    </div>
<?php endif; ?>

<?php $__env->startSection('content'); ?>
    <div class="body-wrapper pb-5">
        <div class="container-fluid">
            <div class="page-hedding">
                <div class="row  g-3">
                    <div class="col-auto">
                        <div class="page-title">
                            <span>Master</span>
                            <h1>HOD Dashboard</h1>
                        </div>
                    </div>
                    <!-- <div class="col-auto"><a class="btn btn-theme" href="#">View Employees</a></div> -->
                </div>
            </div>

            <div class="row g-3 g-xxl-4 ">
                <div class="col-xl-9 col-lg-12">
                    <div class="row g-3 g-xxl-4 card-heigth">
                        <div class="col-12">
                            <div class="card card-talentAcqEmpHOD">
                                <div class="row g-xxl-4 g-md-3 g-2">
                                    <div class="col-md-3 col-sm-6">
                                        <div class="bg-themeGrayLight">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <div>
                                                    <p class="mb-0  fw-500">Total Employees</p>
                                                    <strong><?php echo e($total_employees); ?></strong>
                                                </div>
                                                <a href="#">
                                                    <img src="assets/images/arrow-right-circle.svg" alt=""
                                                        class="img-fluid">
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-sm-6">
                                        <div class="bg-themeGrayLight">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <div>
                                                    <p class="mb-0  fw-500">Present</p>
                                                    <strong><?php echo e($present_employee_counts); ?></strong>
                                                </div>
                                                <a href="#">
                                                    <img src="assets/images/arrow-right-circle.svg" alt=""
                                                        class="img-fluid">
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-sm-6">
                                        <div class="bg-themeGrayLight">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <div>
                                                    <p class="mb-0  fw-500">Absent</p>
                                                    <strong><?php echo e($absent_employee_counts); ?></strong>
                                                </div>
                                                <a href="#">
                                                    <img src="assets/images/arrow-right-circle.svg" alt=""
                                                        class="img-fluid">
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-sm-6">
                                        <div class="bg-themeGrayLight">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <div>
                                                    <p class="mb-0  fw-500">On leave</p>
                                                    <strong><?php echo e($leave_employee_counts); ?></strong>
                                                </div>
                                                <a href="#">
                                                    <img src="assets/images/arrow-right-circle.svg" alt=""
                                                        class="img-fluid">
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="bg-themeGrayLight ">
                                            <div class="card-title">
                                                <h3>Positions</h3>
                                            </div>
                                            <div class="row g-xxl-4 g-sm-3 g-1">
                                                <?php if($resort_positions->count() > 0): ?>
                                                <?php $__currentLoopData = $resort_positions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $position): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                    <div class="col-lg-4 col-sm-4">
                                                        <div class="table-responsive">
                                                            <table class="table-lableNew w-100">
                                                                <tbody>
                                                                    <tr>
                                                                        <td><?php echo e($position->position_title); ?></td>
                                                                        <th><?php echo e($position->employees_count); ?></th>
                                                                    </tr>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                <?php else: ?>
                                                    <div class="col-12">
                                                        <p class="text-center">No positions available.</p>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                        <div class="col-xl-8 col-lg-7">
                            <div class="card">
                                <div class="card-title">
                                    <div class="row g-md-2 g-1 align-items-center">
                                        <div class="col">
                                            <h3 class="text-nowrap">Requests</h3>
                                        </div>
                                        <div class="col-auto">
                                            <a href="#" class="a-link">View All</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="table-responsive">
                                    <table class="table-lableNew table-talentAcqRequests w-100">
                                        <thead>
                                            <tr>
                                                <th>Request Type</th>
                                                <th>Requested By</th>
                                                <th>Date</th>
                                                <th>Status</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>#14521</td>
                                                <td>
                                                    <div class="tableUser-block">
                                                        <div class="img-circle"><img src="assets/images/user-2.svg"
                                                                alt="user">
                                                        </div>
                                                        <span class="userApplicants-btn">John Doe</span>
                                                    </div>
                                                </td>
                                                <td>15 Mar 2025</td>
                                                <td><span class="badge badge-themeSuccess">Approved</span></td>
                                                <td>
                                                    <a href="#" class="eye-btn skyBlue"><i
                                                            class="fa-regular fa-eye"></i></a>
                                                    <a href="#" class="close-btn"><i class="fa-solid fa-xmark"></i></a>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>#14521</td>
                                                <td>
                                                    <div class="tableUser-block">
                                                        <div class="img-circle"><img src="assets/images/user-2.svg"
                                                                alt="user">
                                                        </div>
                                                        <span class="userApplicants-btn">Christian Slater</span>
                                                    </div>
                                                </td>
                                                <td>15 Mar 2025</td>
                                                <td><span class="badge badge-themeYellow">Pending</span></td>
                                                <td>
                                                    <a href="#" class="eye-btn skyBlue"><i
                                                            class="fa-regular fa-eye"></i></a>
                                                    <a href="#" class="correct-btn"><i
                                                            class="fa-solid fa-check"></i></a>
                                                    <a href="#" class="close-btn"><i class="fa-solid fa-xmark"></i></a>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>#14521</td>
                                                <td>
                                                    <div class="tableUser-block">
                                                        <div class="img-circle"><img src="assets/images/user-2.svg"
                                                                alt="user">
                                                        </div>
                                                        <span class="userApplicants-btn">Brijesh Pandey</span>
                                                    </div>
                                                </td>
                                                <td>15 Mar 2025</td>
                                                <td><span class="badge badge-themeSuccess">Approved</span></td>
                                                <td>
                                                    <a href="#" class="eye-btn skyBlue"><i
                                                            class="fa-regular fa-eye"></i></a>
                                                    <a href="#" class="correct-btn"><i
                                                            class="fa-solid fa-check"></i></a>
                                                    <a href="#" class="close-btn"><i class="fa-solid fa-xmark"></i></a>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>#14521</td>
                                                <td>
                                                    <div class="tableUser-block">
                                                        <div class="img-circle"><img src="assets/images/user-2.svg"
                                                                alt="user">
                                                        </div>
                                                        <span class="userApplicants-btn">Seerish Yadav</span>
                                                    </div>
                                                </td>
                                                <td>15 Mar 2025</td>
                                                <td><span class="badge badge-themeYellow">Pending</span></td>
                                                <td>
                                                    <a href="#" class="eye-btn skyBlue"><i
                                                            class="fa-regular fa-eye"></i></a>
                                                    <a href="#" class="correct-btn"><i
                                                            class="fa-solid fa-check"></i></a>
                                                    <a href="#" class="close-btn"><i class="fa-solid fa-xmark"></i></a>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>#14521</td>
                                                <td>
                                                    <div class="tableUser-block">
                                                        <div class="img-circle"><img src="assets/images/user-2.svg"
                                                                alt="user">
                                                        </div>
                                                        <span class="userApplicants-btn">John Doe</span>
                                                    </div>
                                                </td>
                                                <td>15 Mar 2025</td>
                                                <td><span class="badge badge-themeYellow">Pending</span></td>
                                                <td>
                                                    <a href="#" class="eye-btn skyBlue"><i
                                                            class="fa-regular fa-eye"></i></a>
                                                    <a href="#" class="correct-btn"><i
                                                            class="fa-solid fa-check"></i></a>
                                                    <a href="#" class="close-btn"><i class="fa-solid fa-xmark"></i></a>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-4 col-lg-5">
                            <div class="card card-talentAcqRecentActivity">
                                <div class="card-title">
                                    <div class="row g-md-2 g-1 align-items-center">
                                        <div class="col">
                                            <h3 class="text-nowrap">Recent Activity</h3>
                                        </div>
                                        <div class="col-auto">
                                            <a href="#" class="a-link">View All</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="border-bottom">
                                    <h6 class="fw-600">Leave Request REQ-003 Rejected</h6>
                                    <p>2-week vacation request for Mike Johnson denied</p>
                                    <span>15 mins ago</span>
                                </div>
                                <div class="border-bottom">
                                    <h6 class="fw-600">New Position Posted</h6>
                                    <p>Senior Manager role opened in management Team</p>
                                    <span>15 mins ago</span>
                                </div>
                                <div class="border-bottom">
                                    <h6 class="fw-600">Leave Request REQ-003 Rejected</h6>
                                    <p>2-week vacation request for Mike Johnson denied</p>
                                    <span>15 mins ago</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="card card-talentAcqApproveBudgetHOD">
                                <div class="card-title">
                                    <h3>Approved Budgets</h3>
                                </div>
                                <div class="row g-xxl-4 g-md-3 g-2">
                                    <div class="col-md-3 col-sm-6">
                                        <div class="bg-themeGrayLight">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <div>
                                                    <p class="mb-0  fw-500">Total Budget</p>
                                                    <strong>$1.2M</strong>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-sm-6">
                                        <div class="bg-themeGrayLight">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <div>
                                                    <p class="mb-0  fw-500">Spent</p>
                                                    <strong>$345K</strong>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-sm-6">
                                        <div class="bg-themeGrayLight">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <div>
                                                    <p class="mb-0  fw-500">Remaining</p>
                                                    <strong>$855K</strong>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-sm-6">
                                        <div class="bg-themeGrayLight">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <div>
                                                    <p class="mb-0  fw-500">Spent %</p>
                                                    <strong>28.8%</strong>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <h6 class="fw-600 mb-2">Budget Utilization</h6>
                                        <div class="progress progress-custom progress-themeskyblueNew flex-grow-1 me-2">
                                            <div class="progress-bar" role="progressbar" style="width: 32.5%"
                                                aria-valuenow="32.5" aria-valuemin="0" aria-valuemax="100">32.5%
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
                <div class="col-xl-3 col-lg-12">
                    <div class="row g-xxl-4 g-3">
                        <div class="col-xl-12 col-md-6">
                            <div class="card h-auto">
                                <div class="mb-4 overflow-hidden">
                                    <div id="calendar"></div>
                                </div>
                                <div class="card-title">
                                    <div class="row justify-content-between align-items-center g-3">
                                        <div class="col">
                                            <h3>Upcoming Interviews</h3>
                                        </div>

                                    </div>
                                </div>
                                <div id="upinterviews">
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-12 col-md-6">
                            <div class="card card-talentAcqCompliances h-100">
                                <div class="card-title">
                                    <h3>Compliances</h3>
                                </div>
                                <div class="border-bottom">
                                    <h6>Workforce Planning</h6>
                                    <div class="d-flex">
                                        <p>Employees Under Minimum Wage:</p>
                                        <span class="text-danger">03</span>
                                    </div>
                                </div>
                                <div class="border-bottom">
                                    <h6>Time & Attendance</h6>
                                    <div class="d-flex">
                                        <p>Excessive Overtime hours</p>
                                        <span class="text-danger">48</span>
                                    </div>
                                    <div class="d-flex">
                                        <p>Mandatory Break Not Taken</p>
                                        <span class="text-danger">03</span>
                                    </div>
                                </div>
                                <div class="border-bottom">
                                    <h6>Workforce Planning</h6>
                                    <div class="d-flex">
                                        <p>Employees Under Minimum Wage:</p>
                                        <span class="text-danger">03</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-6">
                    <div class="row g-3 g-xxl-4">
                        <div class="col-12">
                            <div class="card card-theme card-talentAcqHRStatistics">
                                <div class="card-title">
                                    <div class="row g-md-2 g-1 align-items-center">
                                        <div class="col">
                                            <h3 class="text-nowrap">HR STATISTICS</h3>
                                        </div>
                                        <div class="col-auto">
                                            <a href="#" class="a-link">View All</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="row g-md-3 g-2">
                                        <div class="col-xxl-6 col-xl-8 col-md-6 col-sm-8">
                                            <div class="bg-themeGrayLight">
                                                <div class="card-title">
                                                    <h3>Employee Type</h3>
                                                </div>
                                                <div class="row g-md-3 g-2 align-items-center">
                                                    <div class="col-sm-6">
                                                        <div class="talentAcqHRStatiEmp-chart">
                                                             <canvas id="myDoughnutChart" data-expatriate_employee ="<?php echo e($expatriate_employees_count); ?>" data-local_employee="<?php echo e($local_employees_count); ?>"></canvas></div>
                                                    </div>
                                                    <div class="col-sm-6">
                                                        <div
                                                            class="row g-2  mb-md-3 mb-2 doughnut-labelTop justify-content-center justify-content-sm-start">
                                                            <div class="col-xl-12 col-auto">
                                                                <div class="doughnut-label">
                                                                    <span class="bg-theme"></span>Local Maldivian
                                                                </div>
                                                            </div>
                                                            <div class="col-xl-12 col-auto">
                                                                <div class="doughnut-label">
                                                                    <span class="bg-themeSkyblue"></span>Expatriate
                                                                    Employees
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-xxl-6 col-xl-4 col-md-6 col-sm-4">
                                            <div class="row g-md-3 g-2">
                                                <div class="col-12">
                                                    <div class="bg-themeGrayLight d-flex">
                                                        <p>Total Budgeted Employees:</p>
                                                        <strong> <?php echo e($manning_response->TotalBudgtedemp ?? '0'); ?></strong>
                                                    </div>
                                                </div>
                                                <div class="col-12">
                                                    <div class="bg-themeGrayLight d-flex">
                                                        <p>Filled Positions</p>
                                                        <strong><?php echo e($manning_response->total_filled_positions_count ?? '0'); ?></strong>
                                                        <div class="progress progress-custom progress-themeBlue">
                                                    
                                                            <?php
                                                                $total_budgeted = $manning_response->total_budgeted_employees ?? 0;
                                                                $filled_positions = $manning_response->total_filled_positions_count ?? 0;
                                                                $filled_percentage = $total_budgeted > 0 ? ($filled_positions / $total_budgeted) * 100 : 0;
                                                            ?>
                                                            <div class="progress-bar" role="progressbar" style="width: <?php echo e($filled_percentage); ?>%"
                                                                aria-valuenow="<?php echo e($filled_percentage); ?>" aria-valuemin="0" aria-valuemax="100">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-12">
                                                    <div class="bg-themeGrayLight d-flex">
                                                        <p>Vacant</p>
                                                        <strong class="fw-bold"><?php echo e($manning_response->total_vacant_count ?? '0'); ?></strong>
                                                    </div>
                                                </div>
                                                <div class="col-12">
                                                    <div class="bg-themeGrayLight">
                                                        <div class="d-flex align-items-center  mb-lg-2 mb-1">
                                                            <div class="progress progress-custom progress-themeBlue flex-grow-1 me-2">
                                                                <div class="progress-bar" role="progressbar" style="width: <?php echo e($male_emp_percentage); ?>%"
                                                                    aria-valuenow="<?php echo e($male_emp_percentage); ?>" aria-valuemin="0" aria-valuemax="100"><?php echo e($male_emp_percentage); ?>%
                                                                </div>
                                                            </div>
                                                            <span>Male</span>
                                                        </div>
                                                        <div class="d-flex align-items-center  mb-lg-2 mb-1">
                                                            <div
                                                                class="progress progress-custom progress-themeskyblue flex-grow-1 me-2">
                                                                <div class="progress-bar" role="progressbar" style="width: <?php echo e($female_emp_percentage); ?>%"
                                                                    aria-valuenow="<?php echo e($female_emp_percentage); ?>" aria-valuemin="0" aria-valuemax="100"><?php echo e($female_emp_percentage); ?>%
                                                                </div>
                                                            </div>
                                                            <span>Female</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="card card-theme card-talentAcqTurnoverRatio">
                                <div class="card-title">
                                    <div class="row g-md-2 g-1 align-items-center">
                                        <div class="col">
                                            <h3 class="text-nowrap">TURNOVER RATIO</h3>
                                        </div>
                                        <div class="col-auto">
                                            <a href="#" class="a-link">View All</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="row g-md-3 g-2 mb-md-4 mb-3">
                                        <div class="col-sm-4">
                                            <div class="bg-themeGrayLight d-flex">
                                                <p>Annual Turnover</p>
                                                <strong>6.5%</strong>
                                            </div>
                                        </div>
                                        <div class="col-sm-4">
                                            <div class="bg-themeGrayLight d-flex">
                                                <p>Quarterly Turnover</p>
                                                <strong>2.1%</strong>
                                            </div>
                                        </div>
                                        <div class="col-sm-4">
                                            <div class="bg-themeGrayLight d-flex">
                                                <p>This Month</p>
                                                <strong>3</strong>
                                            </div>
                                        </div>
                                    </div>
                                    <h6 class="fw-600 mb-2">Industry Average</h6>
                                    <div class="progress progress-custom progress-themeskyblueNew w-100 me-2">
                                        <div class="progress-bar" role="progressbar" style="width: 80%"
                                            aria-valuenow="80" aria-valuemin="0" aria-valuemax="100">80%
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="card card-theme card-talentAcqTimeAttend">
                                <div class="card-title">
                                    <div class="row g-md-2 g-1 align-items-center">
                                        <div class="col">
                                            <h3 class="text-nowrap">TIME AND ATTENDANCE</h3>
                                        </div>
                                        <div class="col-auto">
                                            <a href="<?php echo e(route('resort.timeandattendance.hoddashboard')); ?>" class="a-link">View All</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="row g-md-3 g-2">
                                        <div class="col-sm-6">
                                            <div class="bg-themeGrayLight">
                                                <div class="card-title">
                                                    <div class="row g-2 align-items-center ">
                                                        <div class="col">
                                                            <h3>To Do List</h3>
                                                        </div>
                                                        <div class="col-auto"> <a href="#" class="a-link">View all</a>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="todoList-main">
                                                    <?php $__empty_1 = true; $__currentLoopData = $attendanceDataTodoList; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $todo): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                                        <div class="todoList-block">
                                                                    <?php if($todo->flag == 'previous_day' &&  isset($todo->CheckingOutTime)): ?>

                                                                            <div class="img-circle">
                                                                                <img src="<?php echo e($todo->profileImg); ?>" alt="image">
                                                                            </div>
                                                                        <div>
                                                                            <p>
                                                                                <?php echo e($todo->EmployeeName); ?> is doing Overtime for yesterday
                                                                                <?php echo e($todo->StartTime); ?> to <?php echo e($todo->EndTime); ?>

                                                                                (including overtime: <?php echo e($todo->EndTimeWithOvertime); ?>)
                                                                            </p>
                                                                            <a href="javascript:void(0)"
                                                                                data-todoimage="<?php echo e($todo->profileImg); ?>"
                                                                                data-todoname="<?php echo e($todo->first_name .' '. $todo->last_name); ?>"
                                                                                data-differenceInHours="<?php echo e($todo->differenceInHours); ?>"
                                                                                data-shiftname="<?php echo e($todo->ShiftName); ?>"
                                                                                data-todoshiftstime="<?php echo e($todo->StartTime); ?>"
                                                                                data-todoshiftetime="<?php echo e($todo->EndTime); ?>"
                                                                                data-todoassignedot="<?php echo e($todo->OverTime ?? '-'); ?>"
                                                                                data-totalExtraHours="<?php echo e($todo->differenceInHours); ?>"
                                                                                data-flag="<?php echo e($todo->flag); ?>"
                                                                                    data-CheckingOutTime="<?php echo e($todo->CheckingOutTime); ?>"

                                                                                data-bs-toggle="modal" class="eye-btn OverTimeModel">
                                                                                <i class="fa-regular fa-eye"></i>
                                                                            </a>
                                                                        </div>



                                                                <?php elseif($todo->flag == 'today'): ?>

                                                                            <div class="img-circle">
                                                                                <img src="<?php echo e($todo->profileImg); ?>" alt="image">
                                                                            </div>
                                                                        <div>
                                                                            <p>
                                                                                <?php echo e($todo->EmployeeName); ?>  checkout time is missing is  he/she doing Overtime for today
                                                                                
                                                                            </p>
                                                                            <a href="javascript:void(0)"
                                                                                data-todoimage="<?php echo e($todo->profileImg); ?>"
                                                                                data-todoname="<?php echo e($todo->first_name .' '. $todo->last_name); ?>"
                                                                                data-differenceInHours="<?php echo e($todo->differenceInHours); ?>"
                                                                                data-shiftname="<?php echo e($todo->ShiftName); ?>"
                                                                                data-attendance_id ="<?php echo e($todo->attendance_id); ?>"
                                                                                data-todoshiftstime="<?php echo e($todo->StartTime); ?>"
                                                                                data-todoshiftetime="<?php echo e($todo->EndTime); ?>"
                                                                                data-todoassignedot="<?php echo e($todo->OverTime ?? '-'); ?>"
                                                                                data-totalExtraHours="<?php echo e($todo->differenceInHours); ?>"
                                                                                data-OTStatus="<?php echo e($todo->OTStatus); ?>"
                                                                                data-bs-toggle="modal" class="eye-btn OverTimeModel"><i class="fa-regular fa-eye"></i>
                                                                            </a>
                                                                        </div>


                                                                <?php else: ?>
                                                                    <p>No tasks available for today.</p>
                                                                <?php endif; ?>

                                                        </div>
                                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                                        <div class="todoList-block">
                                                            <p>No tasks available for today.</p>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="bg-themeGrayLight">
                                                <div class="card-title">
                                                    <h3>Overtime history</h3>
                                                </div>
                                                <div class="leaveUser-main">
                                                    <div class="leaveUser-bgBlock mb-1">
                                                        <h6>Total Hours</h6>
                                                        <strong><?php echo e($totalOverallWorkingHours); ?></strong>
                                                    </div>
                                                    <div class="table-responsive mb-md-3 mb-2">
                                                        <table class="table-lableNew w-100">
                                                            <tbody>
                                                                <tr>
                                                                    <td>Normal OT</td>
                                                                    <th><?php echo e($totalNormalWorkingHours); ?></th>
                                                                </tr>
                                                                <tr>
                                                                    <td>Holiday OT</td>
                                                                    <th><?php echo e($totalHolidayWorkingHours); ?></th>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                    <h6 class="mb-md-3 mb-2">Employees with highest number of Ots</h6>
                                                    <?php $__currentLoopData = $rosterData; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $roster): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                        
                                                        <div class="leaveUser-block">
                                                            <div class="img-circle">
                                                                <img src="<?php echo e($roster->profile_picture); ?>" alt="image">
                                                            </div>
                                                            <div>
                                                                <h6><?php echo e($roster->first_name); ?> <?php echo e($roster->last_name); ?></h6>
                                                                <span><?php echo e($roster->totalOvertime); ?> Hours</span>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="bg-themeGrayLight h-auto">
                                                <div id="card-duty">
                                                    <div class=" card-title">
                                                        <div class="row justify-content-between align-items-center g-md-3 g-1">
                                                            <div class="col">
                                                                <h3>Duty Roster</h3>
                                                            </div>
                                                            <div class="col-auto">
                                                                <div class="form-group">
                                                                    <select class="form-select" aria-label="Default select example" id="ResortPosition">
                                                                        <option selected="">All Poistion</option>
                                                                        <?php if($resort_positions->isNotEmpty()): ?>
                                                                            <?php $__currentLoopData = $resort_positions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $position): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                                                <option value="<?php echo e($position->id); ?>"><?php echo e($position->position_title); ?></option>
                                                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                                        <?php endif; ?>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <div class="col-auto">
                                                                <div class="form-group">
                                                                    <input type="text" class="form-control datepicker RosterDate" id="RosterDate">
                                                                </div>
                                                            </div>
                                                            <div class="col-auto"><a href="<?php echo e(route('resort.timeandattendance.hoddashboard')); ?>" class="btn btn-themeSkyblue btn-sm">View All</a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <table id="DutyRoster" class="table  table-timeAtten w-100">
                                                        <thead>
                                                            <tr>
                                                                <th>Employee Name</th>
                                                                <th>Poisition</th>
                                                                <th>Shift</th>
                                                                <th>Action</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>

                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="card card-theme card-talentAcqPeopleRelation">
                                <div class="card-title">
                                    <div class="row g-md-2 g-1 align-items-center">
                                        <div class="col">
                                            <h3 class="text-nowrap">PEOPLE RELATION</h3>
                                        </div>
                                        <div class="col-auto">
                                            <a href="<?php echo e(route('GrievanceAndDisciplinery.Hrdashboard')); ?>" class="a-link">View All</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="card-title">
                                        <div class="row g-2 align-items-center ">
                                            <div class="col">
                                                <h3>Grievances</h3>
                                            </div>
                                            <div class="col-auto"><a href="<?php echo e(route('GrievanceAndDisciplinery.grivance.GrivanceIndex')); ?>" class="a-link">View All</a>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row g-md-4 g-2 mb-md-4 mb-3">
                                        <?php $__currentLoopData = $grivanceSubmissionModel; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $grievance): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <div class="col-sm-6">
                                                <div class="d-flex justify-content-between mb-2 border-bottom pb-2">
                                                    <p class="mb-0"><?php echo e($grievance->category_name); ?></p>
                                                    <p><?php echo e($grievance->count); ?></p>
                                                </div>
                                            </div>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </div>

                                    <div class="card-title">
                                        <div class="row g-2 align-items-center ">
                                            <div class="col">
                                                <h3>Disciplinary</h3>
                                            </div>
                                            <div class="col-auto"> <a href="<?php echo e(route('GrievanceAndDisciplinery.Disciplinary.DisciplinaryIndex')); ?>" class="a-link">View all</a>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row g-md-4 g-2">
                                         <?php $__currentLoopData = $disciplinarySubmissionModel; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $disciplinary): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <div class="col-sm-6">
                                                <div class="d-flex justify-content-between mb-2 border-bottom pb-2">
                                                    <p class="mb-0"><?php echo e($disciplinary->category_name); ?></p>
                                                    <p><?php echo e($disciplinary->count); ?></p>
                                                </div>
                                            </div>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="card card-theme card-talentAcqLearningDevel">
                                <div class="card-title">
                                    <div class="row g-md-2 g-1 align-items-center">
                                        <div class="col">
                                            <h3 class="text-nowrap">LEARNING AND DEVELOPMENT</h3>
                                        </div>
                                        <div class="col-auto">
                                            <a href="<?php echo e(route('learning.hod.dashboard')); ?>" class="a-link">View All</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="row g-md-3 g-2">
                                        <div class="col-md-6">
                                            <div class="bg-themeGrayLight">
                                                <div class="card-title">
                                                    <div class="row g-2 align-items-center ">
                                                        <div class="col">
                                                            <h3>Pending Requests</h3>
                                                        </div>
                                                        <div class="col-auto"> <a href="<?php echo e(route('learning.hod.dashboard')); ?>" class="a-link">View all</a>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="leaveUser-main">
                                                    <?php if(count($pending_learning_request)>0): ?>
                                                        <?php $__currentLoopData = $pending_learning_request; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $request): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                            <div class="leaveUser-block">
                                                                <div>
                                                                    <h6><?php echo e($request->learning->name); ?></h6>
                                                                    <p><?php echo e($request->learning->description); ?></p>
                                                                    <div>
                                                                        <a href="<?php echo e(route('learning.request.details', ['id' => $request->id])); ?>" class="a-linkTheme">
                                                                            View Details
                                                                        </a>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                    <?php else: ?>
                                                        <div class="leaveUser-block">
                                                            <div>
                                                                <p>No Pending Request Found</p>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="bg-themeGrayLight">
                                                <div class="card-title">
                                                    <div class="row g-2 align-items-center ">
                                                        <div class="col">
                                                            <h3 class="text-nowrap">Ongoing Training Progress</h3>
                                                        </div>
                                                        <div class="col-auto"> <a href="#" class="a-link">View all</a>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="table-responsive">
                                                    <table class="table-lableNew table-empLDHod w-100">
                                                        <tr>
                                                            <th>Employee Name</th>
                                                            <th>Training</th>
                                                            <th>Training Period</th>
                                                        </tr>
                                                        <?php $__currentLoopData = $ongoing_tranning; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $training): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                            <tr>
                                                                <td>
                                                                    <div class="tableUser-block">
                                                                        <div class="img-circle"><img src="<?php echo e($training->participant && $training->participant->employee ? App\Helpers\Common::getResortUserPicture($training->participant->employee->Admin_Parent_id) : asset('assets/images/user-2.svg')); ?>"
                                                                                alt="user">
                                                                        </div>
                                                                        <span class="userApplicants-btn"><?php echo e($training->participant && $training->participant->employee ? $training->participant->employee->resortAdmin->full_name : 'N/A'); ?></span>
                                                                    </div>
                                                                </td>
                                                                <td><?php echo e($training->learningProgram->name); ?></td>
                                                                <td><?php echo e(\Carbon\Carbon::parse($training->start_date)->format('d M')); ?> To <?php echo e(\Carbon\Carbon::parse($training->end_date)->format('d M')); ?></td>
                                                            </tr>
                                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-12 col-lg-6">
                            <div class="card card-theme card-talentAcqAnnouncement h-100">
                                <div class="card-title">
                                    <div class="row g-md-2 g-1 align-items-center">
                                        <div class="col">
                                            <h3 class="text-nowrap">ANNOUNCEMENT</h3>
                                        </div>
                                        <div class="col-auto">
                                            <a href="<?php echo e(route('people.announcements')); ?>" class="a-link">View All</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table id="" class="table table-talentAcqAnnouncement w-100 mb-1">
                                            <thead>
                                                <tr>
                                                    <th>Title</th>
                                                    <th>Employee Name</th>
                                                    <th>Publication Date</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php $__currentLoopData = $AnnouncementData; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $announcement): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <tr>
                                                    <td><?php echo e($announcement->category->name); ?></td>
                                                    <td>
                                                        <div class="tableUser-block">
                                                            <div class="img-circle"><img src="<?php echo e(App\Helpers\Common::getResortUserPicture($announcement->Admin_Parent_id)); ?>"
                                                                    alt="user">
                                                            </div>
                                                            <span class="userApplicants-btn"><?php echo e($announcement->employee->resortAdmin->full_name); ?></span>   
                                                        </div>
                                                    </td>
                                                    <td><?php echo e(Carbon\Carbon::parse($announcement->created_at)->format('d M Y')); ?></td>
                                                    <td>
                                                        <?php if($announcement->status === 'Published'): ?>
                                                            <span class="badge badge-themeSuccess"><?php echo e($announcement->status); ?></span>
                                                        <?php elseif($announcement->status === 'Scheduled'): ?>
                                                            <span class="badge badge-themeSkyblue"><?php echo e($announcement->status); ?></span>
                                                        <?php elseif($announcement->status === 'Draft'): ?>
                                                            <span class="badge badge-themeWarning"><?php echo e($announcement->status); ?></span>
                                                        <?php else: ?>
                                                            <span class="badge badge-themeGray"><?php echo e($announcement->status); ?></span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-12 col-lg-6">
                            <div class="card card-theme card-talentAcqProbation h-100">
                                <div class="card-title">
                                    <div class="row g-md-2 g-1 align-items-center">
                                        <div class="col">
                                            <h3 class="text-nowrap">PROBATION</h3>
                                        </div>
                                        <div class="col-auto">
                                            <a href="<?php echo e(route('people.probation')); ?>" class="a-link">View All</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table id="" class="table data-Table  table-peopleProbationList w-100 mb-1">
                                            <thead>
                                                <tr>
                                                    <th>Employee Name</th>
                                                    <th>Position</th>
                                                    <th>Probation End Date</th>
                                                    <th>Onboarding Training</th>
                                                    <th>Monthly Check-in Status,</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php $__currentLoopData = $probationEmployees; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $probationEmployee): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <tr>
                                                    <td>
                                                        <div class="tableUser-block">
                                                            <div class="img-circle"><img src="<?php echo e(App\Helpers\Common::getResortUserPicture($probationEmployee->Admin_Parent_id)); ?>"
                                                                    alt="user">
                                                            </div>
                                                            <span class="userApplicants-btn"><?php echo e($probationEmployee->resortAdmin->full_name); ?></span>
                                                        </div>
                                                    </td>
                                                    <td><?php echo e($probationEmployee->position->position_title); ?></td>
                                                    <td>15 Feb 2022 (Due in 2 days)</td>
                                                    <td><span class="badge badge-themeSuccess">Completed</span></td>
                                                    <td><span class="badge badge-themeSuccess">Up to date</span></td>
                                                </tr>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-6">
                    <div class="row g-3 g-xxl-4">
                        <div class="col-xl-12 col-lg-6">
                            <div class="card card-theme card-talentAcqRecruitment">
                                <div class="card-title">
                                    <div class="row g-md-2 g-1 align-items-center">
                                        <div class="col">
                                            <h3 class="text-nowrap">RECRUITMENT</h3>
                                        </div>
                                        <div class="col-auto">
                                            <a href="#" class="a-link">View All</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="row g-md-3 g-2 mb-2">
                                        <div class="col-sm-4">
                                            <div class="bg-themeGrayLight d-flex">
                                                <p>In Progress</p>
                                                <strong><?php echo e($InProgressApplicants); ?></strong>
                                            </div>
                                        </div>
                                        <div class="col-sm-4">
                                            <div class="bg-themeGrayLight d-flex">
                                                <p>Total Applicants</p>
                                                <strong><?php echo e($TotalApplicantCounts); ?></strong>
                                            </div>
                                        </div>
                                        <div class="col-sm-4">
                                            <div class="bg-themeGrayLight d-flex">
                                                <p>Lined Up Interviews</p>
                                                <strong><?php echo e($Interviews); ?></strong>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-title">
                                        <h3>Lined up interviews</h3>
                                    </div>
                                    <div class="table-responsive">
                                        <table id="" class="table table-talentAcqRecruitment w-100 mb-1">
                                            <thead>
                                                <tr>
                                                    <th>Candidate</th>
                                                    <th>Position</th>
                                                    <th>Date & Time</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if(!$UplcomingApplicants->isEmpty()): ?>
                                                    <?php $__currentLoopData = $UplcomingApplicants; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $interview): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                    <tr>
                                                        <td>
                                                            <div class="tableUser-block">
                                                                <div class="img-circle"><img src="<?php echo e($interview->profileImg); ?>"
                                                                        alt="user">
                                                                </div>
                                                                <span class="userApplicants-btn"><?php echo e($interview->name); ?></span>
                                                            </div>
                                                        </td>
                                                        <td><?php echo e($interview->Position); ?></td>
                                                        <td><?php echo e($interview->InterViewDate); ?> - <?php echo e($interview->ResortInterviewtime); ?></td>
                                                    </tr>
                                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                <?php else: ?>
                                                    <tr>
                                                        <td colspan="3" class="text-center">No Upcoming Interviews</td>
                                                    </tr>
                                                <?php endif; ?>
                                                
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 order-lg-1 order-xl-0">
                            <div class="card card-theme card-talentAcqLeaveHOD">
                                <div class="card-title">
                                    <div class="row g-md-2 g-1 align-items-center">
                                        <div class="col">
                                            <h3 class="text-nowrap">LEAVE</h3>
                                        </div>
                                        <div class="col-auto">
                                            <a href="<?php echo e(route('leave.hoddashboard')); ?>" class="a-link">View All</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="row g-md-3 g-2">
                                        <div class="col-md-6">
                                            <div class="bg-themeGrayLight">
                                                <div class="card-title">
                                                    <div class="row g-1 align-items-center">
                                                        <div class="col">
                                                            <h3>Who's On Leave</h3>
                                                        </div>
                                                        <div class="col-auto">
                                                             <p>Today</p>
                                                            
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="leaveUser-main">
                                                    <?php $__currentLoopData = $todayleaveUsers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $leaveUser): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                        <div class="leaveUser-block">
                                                            <div class="img-circle">
                                                                <img src="<?php echo e(App\Helpers\Common::getResortUserPicture($leaveUser->employee->Admin_Parent_id)); ?>" alt="image">
                                                            </div>
                                                            <div>
                                                                <h6><?php echo e($leaveUser->employee->resortAdmin->full_name); ?></h6>
                                                                <p><?php echo e($leaveUser->employee->department->name); ?> - <?php echo e($leaveUser->employee->position->position_title); ?></p>
                                                            </div>
                                                            <div><span class="badge badge-themeGray "><?php echo e($leaveUser->reason); ?></span>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="bg-themeGrayLight">
                                                <div class="card-title">
                                                    <div class="row g-1 align-items-center">
                                                        <div class="col">
                                                            <h3>Upcoming Leaves</h3>
                                                        </div>
                                                        
                                                    </div>
                                                </div>
                                                <div class="leaveUser-main">
                                                    <?php $__currentLoopData = $upcomingLeaveUsers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $leaveUser): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                        <div class="leaveUser-block">
                                                            <div class="img-circle">
                                                                <img src="<?php echo e(App\Helpers\Common::getResortUserPicture($leaveUser->employee->Admin_Parent_id)); ?>" alt="image">
                                                            </div>
                                                            <div>
                                                                <h6><?php echo e($leaveUser->employee->resortAdmin->full_name); ?></h6>
                                                                <p><?php echo e($leaveUser->employee->department->name); ?> - <?php echo e($leaveUser->employee->position->position_title); ?></p>
                                                                <span class="badge badge-themeNew1"><i
                                                                        class="fa-regular fa-calendar"></i> <?php echo e(Carbon\Carbon::parse($leaveUser->from_date)->format('d-M')); ?> To
                                                                    <?php echo e(Carbon\Carbon::parse($leaveUser->to_date)->format('d-M')); ?></span>
                                                            </div>
                                                            <div><span class="badge badge-themeGray "><?php echo e($leaveUser->reason); ?></span>
                                                                <span class="fw-500">Total: <?php echo e($leaveUser->total_days); ?></span>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-12 col-lg-6 order-lg-0 order-xl-0">
                            <div class="card card-theme">
                                <div class="card-title">
                                    <div class="row g-md-2 g-1 align-items-center">
                                        <div class="col">
                                            <h3 class="text-nowrap">ACCOMMODATION</h3>
                                        </div>
                                        <div class="col-auto">
                                            <a href="<?php echo e(route('resort.accommodation.hoddashboard')); ?>" class="a-link">View All</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table id="" class="table table-talentAcqAccom w-100 mb-1">
                                            <thead>
                                                <tr>
                                                    <th>Employee</th>
                                                    <th>Building name</th>
                                                    <th>Floor number</th>
                                                    <th>Room number</th>
                                                    <th>Bed number</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php $__currentLoopData = $accommodationData; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $accommodation): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <tr>
                                                    <td>
                                                        <div class="tableUser-block">
                                                            <div class="img-circle"><img src="<?php echo e(App\Helpers\Common::getResortUserPicture($accommodation->employee->Admin_Parent_id)); ?>" alt="user">
                                                            </div>
                                                            <span class="userApplicants-btn"><?php echo e($accommodation->employee->resortAdmin->full_name); ?></span>
                                                        </div>
                                                    </td>
                                                    <td><?php echo e($accommodation->availableAccommodation->building->BuildingName ?? '-'); ?></td>

                                                    <td><?php echo e($accommodation->availableAccommodation->Floor ?? '-'); ?></td>
                                                    <td><?php echo e($accommodation->availableAccommodation->RoomNo ?? '-'); ?></td>
                                                    <td><?php echo e($accommodation->availableAccommodation->BedNo ?? '-'); ?></td>
                                                </tr>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-12 col-lg-6 order-lg-2 order-xl-0">
                            <div class="card card-theme card-talentAcqPerforHOD h-100">
                                <div class="card-title">
                                    <div class="row g-md-2 g-1 align-items-center">
                                        <div class="col">
                                            <h3 class="text-nowrap">PERFORMANCE</h3>
                                        </div>
                                        <div class="col-auto">
                                            <a href="<?php echo e(route('Performance.HODdashboard')); ?>" class="a-link">View All</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="row g-md-3 g-2">
                                        <div class="col-sm-4">
                                            <div class="bg-themeGrayLight d-flex">
                                                <p>Appraisal Pending</p>
                                                <strong>12</strong>
                                            </div>
                                        </div>
                                        <div class="col-sm-4">
                                            <div class="bg-themeGrayLight d-flex">
                                                <p>Employees In PIP</p>
                                                <strong>5</strong>
                                            </div>
                                        </div>
                                        <div class="col-sm-4">
                                            <div class="bg-themeGrayLight d-flex">
                                                <p>Employees In PDP</p>
                                                <strong>3</strong>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="bg-themeGrayLight">
                                                <div class="card-title">
                                                    <div class="row g-md-3 g-1 align-items-center">
                                                        <div class="col">
                                                            <h3 class="text-nowrap">Monthly Check-In</h3>
                                                        </div>
                                                        <div class="col-auto"><a href="#" class="a-link">View All</a>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="overflow-auto pe-1">
                                                    <?php $__currentLoopData = $monthlyCheckinPerformance; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $checkin): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                        <div class="monthlyCheck-block">
                                                            <div class="img-circle  userImg-block "><img src="<?php echo e(Common::getResortUserPicture($checkin->employee->Admin_Parent_id)); ?>"
                                                                    alt="user">
                                                            </div>
                                                            <div>
                                                                <div class="d-flex">
                                                                    <div>
                                                                        <h6><?php echo e($checkin->employee->resortAdmin->full_name); ?></h6><span
                                                                            class="badge badge-white"><?php echo e($checkin->employee->Emp_id); ?></span>
                                                                    </div>
                                                                    <span class="badge badge-themeNew1"><i
                                                                            class="fa-regular fa-calendar me-2"></i><?php echo e(Carbon\Carbon::parse($checkin->created_at)->format('d M Y')); ?></span>
                                                                </div>
                                                                <p><?php echo e($checkin->comment); ?></p>
                                                                <a href="<?php echo e(route('Performance.GetMonthlyCheckInDetails', base64_encode($checkin->id))); ?>"
                                                                    class="btn btn-themeYellow btn-small me-xl-3 me-2">View Details</a> 
                                                            </div>
                                                        </div>
                                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                        <div class="col-xl-12 col-lg-6 order-lg-3 order-xl-0">
                            <div class="card card-theme card-talentAcqIncidentHOD h-100">
                                <div class="card-title">
                                    <div class="row g-md-2 g-1 align-items-center">
                                        <div class="col">
                                            <h3 class="text-nowrap">INCIDENT</h3>
                                        </div>
                                        <div class="col-auto">
                                            <a href="<?php echo e(route('incident.hod.dashboard')); ?>" class="a-link">View All</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="row g-md-3 g-2">
                                        <div class="col-sm-6">
                                            <div class="bg-themeGrayLight d-flex">
                                                <p>Total Incidents</p>
                                                <strong><?php echo e($totalIncidentCounts); ?></strong>
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="bg-themeGrayLight d-flex">
                                                <p>Under Investigation</p>
                                                <strong><?php echo e($underInvestigationIncidentCounts); ?></strong>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="col-12">
                                                <div class="bg-themeGrayLight">
                                                    <div class="card-title">
                                                        <div class="row g-md-3 g-1 align-items-center">
                                                            <div class="col">
                                                                <h3 class="text-nowrap">To Do List</h3>
                                                            </div>
                                                            <div class="col-auto"><a href="<?php echo e(route('incident.index')); ?>" class="a-link">View
                                                                    All</a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="leaveUser-main">
                                                        <?php $__currentLoopData = $incidentData; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $incident): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                        <div class="leaveUser-block">
                                                            <div>
                                                                <div class="d-flex">
                                                                    <h6><?php echo e($incident->incident_name); ?></h6>
                                                                    <span class="badge badge-white"><?php echo e(\Carbon\Carbon::parse($incident->created_at)->diffForHumans()); ?></span>
                                                                </div>
                                                                <p><?php echo e($incident->description); ?></p>
                                                                <div>
                                                                    <a href="<?php echo e(route('incident.view', base64_encode($incident->id))); ?>" class="eye-btn skyBlue"><i
                                                                            class="fa-regular fa-eye"></i></a>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-12 col-lg-6 order-lg-4 order-xl-0">
                            <div class="card card-theme  h-100">
                                <div class="card-title">
                                    <div class="row g-md-2 g-1 align-items-center">
                                        <div class="col">
                                            <h3 class="text-nowrap">SOS</h3>
                                        </div>
                                        <div class="col-auto">
                                            <a href="<?php echo e(route('sos.dashboard.index')); ?>" class="a-link">View All</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table id="" class="table table-talentAcqSos w-100 mb-1">
                                            <thead>
                                                <tr>
                                                    <th>Active SOS name</th>
                                                    <th>location</th>
                                                    <th>Date</th>
                                                    <th>Time</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                 <?php $__currentLoopData = $SOSHistory; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $history): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <tr>
                                                    <td><?php echo e($history->getSos->name); ?></td>

                                                    <td><?php echo e($history->location); ?></td>
                                                    <td><?php echo e($history->date); ?></td>
                                                    <td><?php echo e($history->time); ?></td>
                                                    <td>
                                                        <?php if($history->status == 'Completed'): ?>
                                                            <span class="badge badge-themeSuccess"><?php echo e($history->status); ?></span>
                                                        <?php elseif($history->status == 'Active'): ?>
                                                            <span class="badge badge-themeSuccess"><?php echo e($history->status); ?></span>
                                                        <?php elseif($history->status == 'Drill-Active'): ?>
                                                            <span class="badge badge-infoBorder"><?php echo e($history->status); ?></span>
                                                        <?php elseif($history->status == 'Pending'): ?>
                                                        <span class="badge badge-themeWarning"><?php echo e($history->status); ?></span>
                                                        <?php elseif($history->status == 'Rejected' || $history->status == 'Drill-Rejected'): ?>
                                                            <span class="badge badge-themeDanger"><?php echo e($history->status); ?></span>
                                                        <?php else: ?>
                                                            <span class="badge badge-themeGray"><?php echo e($history->status); ?></span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-12 col-lg-6 order-lg-5 order-xl-0">
                            <div class="card card-theme  h-100">
                                <div class="card-title">
                                    <div class="row g-md-2 g-1 align-items-center">
                                        <div class="col">
                                            <h3 class="text-nowrap">EXIT CLEARANCE</h3>
                                        </div>
                                        <div class="col-auto">
                                            <a href="<?php echo e(route('people.exit-clearance.index')); ?>" class="a-link">View All</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table id="" class="table table-talentAcqExitClear  w-100 mb-1">
                                            <thead>
                                                <tr>
                                                    <th>Employee Name</th>
                                                    <th>Position</th>
                                                    <th>Last Working Date</th>
                                                    <th>Clearance Form</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php $__currentLoopData = $EmployeeResignation; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $resign_employee): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <tr>
                                                    <td>
                                                        <div class="tableUser-block">
                                                            <div class="img-circle"><img src="<?php echo e(App\Helpers\Common::getResortUserPicture($resign_employee->employee->Admin_Parent_id)); ?>"
                                                                    alt="user">
                                                            </div>
                                                            <span class="userApplicants-btn"><?php echo e($resign_employee->employee->resortAdmin->full_name); ?></span>
                                                        </div>
                                                    </td>
                                                    <td><?php echo e($resign_employee->employee->department->name); ?></td>
                                                    <td>
                                                        <?php echo e(\Carbon\Carbon::parse($resign_employee->last_working_date)->format('d M Y')); ?> 
                                                          <?php if(\Carbon\Carbon::parse($resign_employee->last_working_date)->isToday()): ?>
                                                            Last Working Day
                                                        <?php elseif(\Carbon\Carbon::parse($resign_employee->last_working_date)->isFuture()): ?>
                                                            (Due in <?php echo e(\Carbon\Carbon::parse($resign_employee->last_working_date)->diffForHumans()); ?>)
                       
                                                        <?php endif; ?></td>
                                                    <td>
                                                        <?php if($resign_employee->assignedForm->status == 'Pending'): ?>
                                                            <span class="badge badge-themeWarning"><?php echo e($resign_employee->assignedForm->status); ?></span>
                                                        <?php elseif($resign_employee->assignedForm->status == 'Completed'): ?>
                                                            <span class="badge badge-themeSuccess"><?php echo e($resign_employee->assignedForm->status); ?></span>
                                                        <?php else: ?>
                                                            <span class="badge badge-themeGray"><?php echo e($resign_employee->assignedForm->status); ?></span>
                                                        <?php endif; ?>
                                                        
                                                    </td>
                                                </tr>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('import-css'); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('import-scripts'); ?>
<script>   
    $(function () {
        var todayDate = moment().startOf('day');
        var YM = todayDate.format('YYYY-MM');
        var YESTERDAY = todayDate.clone().subtract(1, 'day').format('YYYY-MM-DD');
        var TODAY = todayDate.format('YYYY-MM-DD');
        var TOMORROW = todayDate.clone().add(1, 'day').format('YYYY-MM-DD');
        var cal = $('#calendar').fullCalendar({
            header: {
                left: 'prev',
                center: 'title',
                right: 'next'
            },
            editable: true,
            eventLimit: 0, 
            navLinks: true,
            events: function(start, end, timezone, callback) {
                let Resort_id = $("#Dasboard_resort_id").val();

                $.ajax({
                    url: "<?php echo e(route('resort.ta.GetDateclickWiseUpcomingInterview')); ?>",
                    type: "POST",
                    data: {
                        start: start.format('YYYY-MM-DD'),
                        end: end.format('YYYY-MM-DD'),
                        Resort_id: Resort_id,
                        "_token": "<?php echo e(csrf_token()); ?>",
                    },
                    success: function(response) {
                        $("#upinterviews").html(response.view);
                        $('.fc-day').removeClass('custom-dot');

                        response.dates.forEach(function(date) {
                            let formattedDate = moment(date).format('YYYY-MM-DD');
                            let dayCell = $(`.fc-day[data-date="${formattedDate}"]`);
                            if (dayCell.length)
                            {
                                dayCell.addClass('custom-dot');
                            }
                        });
                        callback([]);
                    },
                    error: function(xhr) {
                        console.error("Error fetching interview dates", xhr);
                    }
                });
            },
            dayClick: function(date, jsEvent, view) {

                    let Resort_id = $("#Dasboard_resort_id").val();
                    $.ajax({
                        url: "<?php echo e(route('resort.ta.GetDateclickWiseUpcomingInterview')); ?>",
                        type: "POST",
                        data: {
                            date: date.format('YYYY-MM-DD'),
                            Resort_id: Resort_id,
                            "_token": "<?php echo e(csrf_token()); ?>"
                        },
                        success: function(response) {

                            if (response.success) {
                                $("#upinterviews").html(response.view);

                            } else {
                                // Display error message if success is false
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
                                    console.log(error);
                                    errs += error + '<br>';
                                });
                            } else {
                                errs = "An unexpected error occurred.";
                            }

                            toastr.error(errs, {
                                positionClass: 'toast-bottom-right'
                            });
                        }
                    });
                }
        });
    });


    $('#respond-HoldModel').on('shown.bs.modal', function () {
        $('#calendarModal').fullCalendar('render');
    });

    $('#sendRequest-modal').on('shown.bs.modal', function () {
        $('#calendarModalSendInterView').fullCalendar('render');
    });

    $(function () {
        var todayDate = moment().startOf('day');
        var YM = todayDate.format('YYYY-MM');
        var YESTERDAY = todayDate.clone().subtract(1, 'day').format('YYYY-MM-DD');
        var TODAY = todayDate.format('YYYY-MM-DD');
        var TOMORROW = todayDate.clone().add(1, 'day').format('YYYY-MM-DD');

        // Calendar for respond modal
        $('#calendarModal').fullCalendar({
            header: {
                    left: 'prev',
                    center: 'title',
                    right: 'next'
                },
                editable: true,
                eventLimit: 0,
                navLinks: true,
                selectable: true,
                select: function(start, end) {
                    var selectedStartDate = start.format('YYYY-MM-DD');  // Format as you need
                    $("#HoldDate").val(selectedStartDate);
                    isDateSelected = true;
                    $("#respond-HoldModel").modal("show");
                },
        });

        // Calendar for send request modal
        $('#calendarModalSendInterView').fullCalendar({
            header: {
                    left: 'prev',
                    center: 'title',
                    right: 'next'
                },
                editable: true,
                eventLimit: 0,
                navLinks: true,
                selectable: true, // Add this line
                select: function(start, end) {
                    var selectedStartDate = start.format('YYYY-MM-DD');  // Format as you need
                    $("#InterviewDate").val(selectedStartDate);
                    $("#TimeSlotsFormdate").val(selectedStartDate);
                    $("#sendRequest-modal").modal("show");
                }
        });
    });

</script>

    <script type="module">
        var ctr = document.getElementById('myDoughnutChart').getContext('2d');
        var expatriateEmployees = parseInt(document.getElementById('myDoughnutChart').getAttribute('data-expatriate_employee'));
        var localEmployees = parseInt(document.getElementById('myDoughnutChart').getAttribute('data-local_employee'));

        // Custom plugin only registered for this chart
        const doughnutLabels = {
            id: 'doughnutLabels',
            afterDraw: function (chart) {
            var ctx = chart.ctx;
            chart.data.datasets.forEach(function (dataset, i) {
                var meta = chart.getDatasetMeta(i);
                if (!meta.hidden) {
                meta.data.forEach(function (element, index) {
                    var dataValue = dataset.data[index];
                    var total = dataset.data.reduce(function (acc, val) {
                    return acc + val;
                    }, 0);
                    var percentage = ((dataValue / total) * 100).toFixed(0) + '%';

                    var position = element.tooltipPosition();

                    ctx.fillStyle = '#fff';
                    ctx.font = 'normal 14px Poppins';
                    ctx.textAlign = 'center';
                    ctx.textBaseline = 'middle';

                    ctx.fillText(percentage, position.x, position.y);
                });
                }
            });
            }
        };

        // Custom plugin for center text
        var myDoughnutChart = new Chart(ctr, {
            type: 'doughnut',
            data: {
            labels: ['Local Maldivian', 'Expatriate Employees'],
            datasets: [{
                data: [localEmployees, expatriateEmployees],
                backgroundColor: ['#2EACB3', '#014653'],
                borderWidth: 0
            }]
            },
            options: {
            responsive: true,
            plugins: {
                doughnutLabels: true, // Enable the custom plugin
                legend: {
                display: false
                }
            },
            layout: {
                padding: {
                top: 10,
                bottom: 10,
                left: 0,
                right: 0
                }
            },
            },
            plugins: [doughnutLabels] // Attach the plugin to this chart only
        });

    </script>

    <script>
        $(document).ready(function() {

            $(document).on("click", ".respondOfFreshmodal", function() {

                // FreshRespond-modal
                $('#FreshRespond-modal').modal('show');
                var image= $(this).attr("data-images");
                var name = $(this).attr("data-name");
                var position = $(this).attr("data-position");
                var department = $(this).attr("data-departmentname");
                var NoOfVacnacy = $(this).attr("data-NoOfVacnacy");
                var rank = $(this).attr('data-rank');
                var ta_id= $(this).attr('data-ta_id');
                var Child_ta_id= $(this).attr('data-Child_ta_id');

                $("#holdResponseModel").attr("data-ta_id",ta_id);
                $("#RejectResponseModel").attr("data-ta_id",ta_id);
                $("#ApprovedResponseModel").attr("data-ta_id",ta_id);
                $("#ApprovedResponseModel").attr("data-Child_ta_id",Child_ta_id);

                $("#holdResponseModel").attr("data-Child_ta_id",Child_ta_id);
                $("#RejectResponseModel").attr("data-Child_ta_id",Child_ta_id);


                let hm =`<div class="respond-block">
                                    <div class="img-circle">
                                        <img src="${image}" alt="image">
                                    </div>
                                    <div>
                                        <h6>${department} (${rank})</h6>
                                        <p>Requested for Hire ${NoOfVacnacy} ${position}</p>
                                    </div>

                        </div>`;
                    $(".respond-main").html(hm);
            });

            // Hold Request Start

            $(document).on("click", "#holdResponseModel", function() {
                var Child_ta_id= $(this).attr('data-Child_ta_id');

                $("#Calender_ta_id").val(Child_ta_id);


            });
            $(document).on("click", ".destoryApplicant", function() {
                var base64_id= $(this).attr('data-id');
                var location= $(this).attr('data-location');
                    $.ajax({
                        url: "<?php echo e(route('resort.ta.destoryApplicant')); ?>",
                        type: "POST",
                        data: {base64_id:base64_id,"_token":"<?php echo e(csrf_token()); ?>" },

                        success: function(response) {
                            $('#respond-rejectModal').modal('hide');
                            if (response.success)
                            {

                                    toastr.success(response.message, "Success", {
                                        positionClass: 'toast-bottom-right'
                                    });
                                    $("#talentPool_"+location).remove();

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
                                console.log(error);
                                errs += error + '<br>';
                            });
                            toastr.error(errs, { positionClass: 'toast-bottom-right' });
                        }
                    });

            });

            $('#HoldNewVacanciyForm').validate({
                rules: {
                    HoldDate: {
                        required: true,
                    }
                },
                messages: {
                    HoldDate: {
                        required: "Please select Hold Date.",
                    }
                },
                submitHandler: function(form) {
                    var formData = new FormData(form);
                    if (!isDateSelected) {

                        toastr.error("Please select a date from the calendar.", "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                        return false;
                    }

                    $.ajax({
                        url: "<?php echo e(route('resort.ta.HiringNotification')); ?>",
                        type: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            $('#respond-HoldModel').modal('hide');
                            if (response.success)
                            {
                                $("#FreshHiringRequest").html(response.view);
                                toastr.success(response.message, "Success", {
                                    positionClass: 'toast-bottom-right'
                                });

                            } else {
                                toastr.error(response.message, "Error", {
                                    positionClass: 'toast-bottom-right'
                                });
                            }
                        }

                    });
                }
            });

            // End of Hold Request.

            // Reject Vacanciy form
            $(document).on("click", "#RejectResponseModel", function() {
                var Child_ta_id= $(this).attr('data-Child_ta_id');

                $("#Rejectio_ta_id").val(Child_ta_id);

            });

            $('#rejectionNewVacanciyForm').validate({
                rules: {
                    New_Vacancy_Rejected: {
                        required: true,
                    }
                },
                messages :
                {
                    New_Vacancy_Rejected: {
                        required: "Please Enter Reason.",
                    }
                },
                submitHandler: function(form) {

                    var formData = new FormData(form);

                    $.ajax({
                        url: "<?php echo e(route('resort.ta.RejectionVcancies')); ?>",
                        type: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            $('#respond-rejectModal').modal('hide');
                            if (response.success)
                            {

                                $("#FreshHiringRequest").html(response.view);
                                    toastr.success(response.message, "Success", {
                                        positionClass: 'toast-bottom-right'
                                    });

                            } else {
                                toastr.error(response.message, "Error", {
                                    positionClass: 'toast-bottom-right'
                                });
                            }
                        }
                    });
                }
            });

            // End of Reject Vacanciy form
            //  Approval

            $('#link_Expiry_date').datepicker({
                format: 'dd/mm/yyyy',
                autoclose: true,
                todayHighlight: true
            });
            $("#ApprovedResponseModel").on("click",function(){
                var ta_id= $(this).attr('data-ta_id');
                var Child_ta_id = $(this).attr('data-Child_ta_id');
                $.ajax({
                        url: "<?php echo e(route('resort.ta.ApprovedVcancies')); ?>",
                        type: "POST",
                        data: {ta_id:ta_id,Child_ta_id:Child_ta_id,"_token":"<?php echo e(csrf_token()); ?>" },

                        success: function(response) {
                            $('#respond-rejectModal').modal('hide');
                            if (response.success)
                            {
                                $('#respond-approvalModal').modal('show');
                                $("#FreshHiringRequest").html(response.view);
                                $(".todoList-main").html(response.Todolistview);
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
                                console.log(error);
                                errs += error + '<br>';
                            });
                            toastr.error(errs, { positionClass: 'toast-bottom-right' });
                        }
                    });
            });

            $(document).on("click", ".jobAD-modal", function () {
                $("#jobAD-modal").modal("show");

                // Fetch data attributes
                let applicationUrlShow = $(this).data("applicationurlshow");
                let applicantLink = $(this).data("applicant_link");
                let jobAdv = $(this).data("jobadvertisement");
                let jobLink = $(this).data("link");
                let childId = $(this).data("ta_childid");
                let expiryDate = $(this).data("expirydate");
                let sourceLinks = $(this).data("source_links"); // This is the new data attribute

                // Set values in the modal
                $(".ta_child_id").val(childId);
                $(".AppendJobAdvLink").attr("href", applicantLink).text(applicationUrlShow);

                if (jobLink === "") {
                    $(".AppendJobAdvLink")
                        .attr("href", applicantLink)
                        .text(applicationUrlShow)
                        .attr("data-disabled", "true")
                        .addClass("ta-adv-disabled");
                    $(".Resort_id").val($(this).attr("data-Resort_id"));
                } else {
                    $(".AppendJobAdvLink")
                        .attr("href", applicantLink)
                        .text(applicationUrlShow)
                        .attr("data-disabled", "false")
                        .removeClass("ta-adv-disabled");
                    $("#link_Expiry_date").addClass("link_Expiry_date_" + childId);
                    $(".link_Expiry_date_" + childId).attr("disabled", "true");
                }

                if (expiryDate) {
                    var parts = expiryDate.split("-");
                    var formattedDate = parts[2] + "/" + parts[1] + "/" + parts[0];
                    $("#link_Expiry_date").datepicker("setDate", formattedDate);
                }

                $(".link_Job").val(applicantLink).addClass("link_Job_");
                $("#JobAdvertisementImage").attr("src", jobAdv);
                $(".DowloadAdvertisement").attr("data-hrefLink", jobAdv);

                // Handle Source Links
                let sourceLinksList = $("#sourceLinksList");
                let sourceLinksHidden = $("#sourceLinksHidden");
                sourceLinksList.empty(); // Clear previous links

                if (sourceLinks && sourceLinks.length) {
                    sourceLinksHidden.val(JSON.stringify(sourceLinks)); // Save links in hidden input
                    sourceLinks.forEach((link) => {
                        let listItem = $("<li></li>");
                        let anchor = $("<a></a>")
                            .attr("href", link)
                            .attr("target", "_blank")
                            .text(link);
                        listItem.append(anchor);
                        sourceLinksList.append(listItem);
                    });
                } else {
                    sourceLinksHidden.val(applicantLink); // Save default applicant link in hidden input
                    sourceLinksList.append(`<li><a href="${applicantLink}" target="_blank">${applicantLink}</a></li>`); // Display the default applicant link
                }
            });

            $(".AppendJobAdvLink").on('click', function (e) {
                if ($(this).attr('data-disabled') === 'true')
                {
                    e.preventDefault();
                }
            });

            $(document).on("click", ".DowloadAdvertisement", function() {

                var fileName = $(this).attr('data-hrefLink');

                var link = document.createElement('a');

                link.href = fileName;

                link.download = fileName.split('/').pop();  // This extracts the file name from the URL

                document.body.appendChild(link);

                link.click();

                document.body.removeChild(link);

            });

            //  jobAD-form
            $('#jobAD-form').validate({
                rules: {
                    link_Expiry_date: {
                        required: true,
                    }
                },
                messages :
                {
                    link_Expiry_date: {
                        required: "Please Select Expiry Date.",
                    }
                },
                submitHandler: function(form) {
                    var formData = new FormData(form);
                    $.ajax({
                        url: "<?php echo e(route('resort.ta.GenrateAdvLink')); ?>",
                        type: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            $('#respond-rejectModal').modal('hide');
                            if (response.success)
                            {
                                $(form)
                                .find('a')
                                .removeClass('ta-adv-disabled')
                                .attr('data-disabled', 'false')
                                $("#FreshHiringRequest").html(response.view);
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
                                console.log(error);
                                errs += error + '<br>';
                            });
                            toastr.error(errs, { positionClass: 'toast-bottom-right' });
                        }
                    });
                }
            });

            //SortListed Employee
            $(document).on("click", ".SortlistedEmployee", function()
            {
                let resort_id= $(this).data('resort_id');
                let ApplicantID= $(this).data('applicantid');
                let ApplicantStatus_id= $(this).data('applicantstatus_id');
                $("#Resort_id").val(resort_id);
                $("#ApplicantID").val(ApplicantID);
                $("#ApplicantStatus_id").val(ApplicantStatus_id);
                $("#sendRequest-modal").modal("show");
            });

            $('#InterviewRequestSentForm').validate({
                rules: {
                    InterviewDate: {
                        required: true,
                    }
                },
                messages :
                {
                    InterviewDate: {
                        required: "Please Select Inteview Date.",
                    }
                },
                submitHandler: function(form) {
                    let Resort_id = $("#Resort_id").val();
                    let ApplicantID = $("#ApplicantID").val();
                    let ApplicantStatus_id = $("#ApplicantStatus_id").val();
                    let InterviewDate = $('#InterviewDate').val();

                    $.ajax({
                        url: "<?php echo e(route('resort.ta.ApplicantTimeZoneget')); ?>",
                        type: "POST",
                        data:{InterviewDate:InterviewDate,Resort_id:Resort_id, ApplicantID:ApplicantID, ApplicantStatus_id:ApplicantStatus_id,"_token":"<?php echo e(csrf_token()); ?>"},

                        success: function(response) {
                            if (response.success)
                            {
                                toastr.success(response.message, "Success", {
                                            positionClass: 'toast-bottom-right'
                                });
                                InterViewDate = response.InterviewDate;
                                $("#sendRequest-modal").modal("hide");
                                $("#TimeSlots-modal").modal("show");
                                $(".sendRequestTime-main").html(response.view);

                            }
                            else
                            {
                                toastr.error(response.message, "Error", {
                                    positionClass: 'toast-bottom-right'
                                });
                            }
                        }
                    });
                }
            });

            $(document).on("focus", '[name^="MalidivanManualTime"], [name^="ApplicantManualTime"]', function () {
                $(".row_time").removeClass("active").find("input").prop("disabled", false);
                $('[name^="ApplicantInterviewtime"]').val('');
                $('[name^="ResortInterviewtime"]').val('');
            });
            $(document).on("change", '[name="MalidivanManualTime"]', function () {
                const timeValue = $(this).val(); // Get the value of the time input
                if (timeValue) {
                    const [hours, minutes] = timeValue.split(":"); // Split the time into hours and minutes
                    const period = hours >= 12 ? "PM" : "AM"; // Determine AM or PM
                    const formattedHours = hours % 12 || 12; // Convert to 12-hour format
                    let MalidivanManualTime1 = formattedHours +":"+minutes+" "+period;
                    $('[name="MalidivanManualTime1"]').val(MalidivanManualTime1); // Display in console
                } else {
                    console.log("No time selected");
                }
            });
            $(document).on("change", '[name="ApplicantManualTime"]', function () {
                const timeValue = $(this).val(); // Get the value of the time input
                if (timeValue) {
                    const [hours, minutes] = timeValue.split(":"); // Split the time into hours and minutes
                    const period = hours >= 12 ? "PM" : "AM"; // Determine AM or PM
                    const formattedHours = hours % 12 || 12; // Convert to 12-hour format
                    let ApplicantManualTime1 = formattedHours +":"+minutes+" "+period;
                    $('[name="ApplicantManualTime1"]').val(ApplicantManualTime1); // Display in console
                } else {
                    console.log("No time selected");
                }
            });

            $(document).on("click", ".Timezone_checkBox", function() {
                // Remove 'Active' class and enable all other rows
                $(".row_time").not($(this).closest(".row_time")).removeClass("active").find("input").prop("disabled", false);

                // Toggle 'active' class on the clicked row
                $(this).closest(".row_time").toggleClass("active");
                let location = $(this).data('id');

                if ($(this).closest(".row_time").hasClass("active")) {
                    // Disable other rows and clear all input values
                    $(".row_time").not($(this).closest(".row_time")).find("input").prop("disabled", true);
                    $('[name^="ApplicantInterviewtime"]').val('');
                    $('[name^="ResortInterviewtime"]').val('');

                    // Retrieve and set the data attributes for the selected row
                    let ApplicantInterviewtime = $(this).data('applicantinterviewtime');
                    let ResortInterviewtime = $(this).data('resortinterviewtime');

                    // Check if data attributes are undefined or null before setting
                    if (ApplicantInterviewtime) {
                        $("#ApplicantInterviewtime_" + location).val(ApplicantInterviewtime);
                    }

                    if (ResortInterviewtime) {
                        $("#ResortInterviewtime_" + location).val(ResortInterviewtime);
                    }
                } else {
                    // Enable all rows if no row is active
                    $(".row_time").find("input").prop("disabled", false);
                }
            });


            $('#TimeSlotsForm').validate({
                rules: {
                        SlotBook: {
                            required: function (element) {
                                // Require SlotBook only if both ManualTime fields are empty
                                return (
                                    $('[name="MalidivanManualTime"]').val().trim() === "" &&
                                    $('[name="ApplicantManualTime"]').val().trim() === ""
                                );
                            },
                        },
                        MalidivanManualTime: {
                            required: function (element) {
                                // Require ManualTime fields only if SlotBook is not selected
                                return $('[name="SlotBook"]:checked').length === 0;
                            },
                        },
                        ApplicantManualTime: {
                            required: function (element) {
                                // Same condition for the second ManualTime field
                                return $('[name="SlotBook"]:checked').length === 0;
                            },
                        },
                    },
                    messages: {
                        SlotBook: {
                            required: "Please select a valid time slot or enter a manual time.",
                        },
                        MalidivanManualTime: {
                            required: "Please enter Malidivan Manual Time or select a valid time slot.",
                        },
                        ApplicantManualTime: {
                            required: "Please enter Applicant Manual Time or select a valid time slot.",
                        },
                    },
                errorPlacement: function(error, element) {
                    if (element.hasClass("Timezone_checkBox")) {
                        // Append error message after the .row_time element
                        element.closest(".row_time").after(error);
                        } else {
                            error.insertAfter(element); // Default behavior
                        }
                    },
                    submitHandler: function(form) {
                        var formData = new FormData(form);

                        $.ajax({
                        url: "<?php echo e(route('resort.ta.InterviewRequest')); ?>",
                        type: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,

                        success: function(response) {
                            if (response.success) {
                                toastr.success(response.message, "Success", {
                                    positionClass: 'toast-bottom-right'
                                });


                                $("#sendRequest-modal").modal("hide");
                                $("#TimeSlots-modal").modal("hide");
                                $(".sendRequestTime-main").html(response.view);
                                $("#todoList-main").html( response.TodoDataview);
                                console.log(response,response.Final_response_data);
                                $("#Final_response_data").html(response.Final_response_data);
                                $("#sendRequestFinal-modal").modal("show");
                                
                            } else {
                                toastr.error(response.message, "Error", {
                                    positionClass: 'toast-bottom-right'
                                });
                            }
                        }
                    });
                }
            });

        });
    </script>

    
    <script>
         $(document).ready(function () {
            $('#ResortPosition').select2({
                placeholder: "Select a Poitions", // Placeholder text
                allowClear: true // Adds a clear (X) button to reset the dropdown
            });

            DutyRosterList();

            $('#ResortPosition').on('change', function () {
                DutyRosterList();
            });
            $('#RosterDate').on('change', function () {
                DutyRosterList();
            });
            $('#RosterDate').datepicker({
                format: 'dd/mm/yyyy',
                autoclose: true,
                todayHighlight: true
            }).datepicker('setDate', new Date());

        });
        
        function DutyRosterList(){

            if ($.fn.DataTable.isDataTable('#DutyRoster')){
                $('#DutyRoster').DataTable().destroy();
            }
            var DutyRoster = $('#DutyRoster').DataTable({
                searching: false,
                bLengthChange: false,
                bFilter: true,
                bInfo: true,
                bAutoWidth: false,
                scrollX: true,
                iDisplayLength: 6,
                processing: true,
                serverSide: true,
                ajax: {
                    url: "<?php echo e(route('resort.timeandattendance.DutyRosterdashboardTable')); ?>",
                    type: 'GET',
                    data: function(d) {
                        d.ResortDepartment = $("#ResortDepartment").val();
                        d.RosterDate = $('#RosterDate').val();
                        d.Positions  = $('#ResortPosition').val();
                    }
                },
                columns: [
                    { data: 'EmployeeName', name: 'EmployeeName', render: function (data, type, row) {
                        return `<div class="tableUser-block">
                            <div class="img-circle"><img src="${row.profileImg}" alt="user"></div>
                            <span class="userApplicants-btn" data-id="${row.id}">${row.EmployeeName}</span>
                        </div>`;
                    }},
                    { data: 'Position', name: 'Position' },
                    { data: 'Shift', name: 'Shift' },
                    { data: 'action', name: 'action', orderable: false, searchable: false }
                ]
            });
        }

    </script>
<?php $__env->stopSection(); ?>


<?php echo $__env->make('resorts.layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\wamp64\www\Wisdom-Ai\HRVMS-Wisdom-AI\resources\views/resorts/master-dashboard/hoddashboard.blade.php ENDPATH**/ ?>