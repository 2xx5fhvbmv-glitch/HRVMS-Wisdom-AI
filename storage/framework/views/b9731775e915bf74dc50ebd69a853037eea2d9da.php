<?php $__env->startSection('page_tab_title' ,"Dashboard"); ?>

<?php if(session('error')): ?>
    <div class="alert alert-danger">
        <?php echo e(session('error')); ?>

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
                            <h1>HR Dashboard</h1>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row g-3 g-xxl-4 card-heigth">
                <div class="col-xl-9 col-lg-12">
                    <div class="card card-talentAcqDivisions">
                        <div class="row g-xxl-4 g-3">
                            <div class="col-md-4 col-sm-6">
                                <div class="bg-themeGrayLight">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div>
                                            <p class="mb-0  fw-500">Divisions</p>
                                            <strong><?php echo e($resort_divisions_count); ?></strong>
                                        </div>
                                        <a href="javascript:void(0);">
                                            <img src="assets/images/arrow-right-circle.svg" alt="" class="img-fluid">
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 col-sm-6">
                                <div class="bg-themeGrayLight">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div>                                            <p class="mb-0  fw-500">Departments</p>
                                            <strong><?php echo e($resort_departments_count); ?></strong>
                                        </div>
                                        <a href="javascript:void(0);">
                                            <img src="assets/images/arrow-right-circle.svg" alt="" class="img-fluid">
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="bg-themeGrayLight">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div>
                                            <p class="mb-0 fw-500">Positions</p>
                                            <strong><?php echo e($resort_positions_count); ?></strong>
                                        </div>
                                        <a href="javascript:void(0);">
                                            <img src="assets/images/arrow-right-circle.svg" alt="" class="img-fluid">
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="bg-themeGrayLight full">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <p class="mb-0 fw-500">Total Employees</p>
                                        <strong><?php echo e($total_employees); ?></strong>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 col-sm-6">
                                <div class="bg-themeGrayLight">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div>
                                            <p class="mb-0 fw-500">Total Present</p>
                                            <strong><?php echo e($present_employee_counts); ?></strong>
                                        </div>
                                        <a href="#">
                                            <img src="assets/images/arrow-right-circle.svg" alt="" class="img-fluid">
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 col-sm-6">
                                <div class="bg-themeGrayLight">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div>
                                            <p class="mb-0 fw-500">On Leave</p>
                                            <strong><?php echo e($leave_employee_counts); ?></strong>
                                        </div>
                                        <a href="#">
                                            <img src="assets/images/arrow-right-circle.svg" alt="" class="img-fluid">
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="bg-themeGrayLight">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div>
                                            <p class="mb-0  fw-500">Absent</p>
                                            <strong><?php echo e($absent_employee_counts); ?></strong>
                                        </div>
                                        <a href="#">
                                            <img src="assets/images/arrow-right-circle.svg" alt="" class="img-fluid">
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-4 col-sm-6">
                                <div class="row g-xxl-4 g-3">
                                    <div class="col-12">
                                        <div class="bg-themeGrayLight ">
                                            <div class="card-title">
                                                <h3>Talent Acquisition</h3>
                                            </div>
                                            <div class="table-responsive">
                                                <table class="table-lableNew  w-100">
                                                    <tbody>
                                                        <tr>
                                                            <td>Total Applications</td>
                                                            <th><?php echo e($total_application_for_job_in_review); ?></th>
                                                        </tr>
                                                        <tr>
                                                            <td>Interviews</td>
                                                            <th><?php echo e($total_selected_applications_with_interviews); ?></th>
                                                        </tr>
                                                        <tr>
                                                            <td>Hired</td>
                                                            <th><?php echo e($total_hired_candidates); ?></th>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="bg-themeGrayLight ">
                                            <div class="card-title">
                                                <h3>People Relation</h3>
                                            </div>
                                            <div class="table-responsive">
                                                <table class="table-lableNew  w-100">
                                                    <tbody>
                                                        <tr>
                                                            <td>Open Cases (Grievance)</td>
                                                            <th><?php echo e($open_grivance_count); ?></th>
                                                        </tr>
                                                        <tr>
                                                            <td>Pending Cases (Grievance)</td>
                                                            <th><?php echo e($pending_grivance_count); ?></th>
                                                        </tr>
                                                        <tr>
                                                            <td>Closed Cases (Grievance)</td>
                                                            <th><?php echo e($resolve_grivance_count); ?></th>
                                                        </tr>
                                                        <tr>
                                                            <td>Open Cases (Disciplinary)</td>
                                                            <th><?php echo e($open_disciplinary_count); ?></th>
                                                        </tr>
                                                        <tr>
                                                            <td>Pending Cases (Disciplinary)</td>
                                                            <th><?php echo e($pending_disciplinary_count); ?></th>
                                                        </tr>
                                                        <tr>
                                                            <td>Closed Cases (Disciplinary)</td>
                                                            <th><?php echo e($resolve_disciplinary_count); ?></th>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="bg-themeGrayLight ">
                                            <div class="card-title">
                                                <h3>File Management</h3>
                                            </div>
                                            <div class="table-responsive">
                                                <table class="table-lableNew  w-100">
                                                    <tbody>
                                                        <tr>
                                                            <td>Total Documents</td>
                                                            <th><?php echo e($TotalDocument); ?></th>
                                                        </tr>
                                                        <tr>
                                                            <td>Unassigned Documents</td>
                                                            <th><?php echo e($UnassignedDocumentsCounts); ?></th>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-4 col-sm-6">
                                <div class="row g-xxl-4 g-3">
                                    <div class="col-12">
                                        <div class="bg-themeGrayLight ">
                                            <div class="card-title">
                                                <h3>Performance *</h3>
                                            </div>
                                            <div class="table-responsive">
                                                <table class="table-lableNew  w-100">
                                                    <tbody>
                                                        <tr>
                                                            <td>Appraisal Pending</td>
                                                            <th>12</th>
                                                        </tr>
                                                        <tr>
                                                            <td>Employees In PIP</td>
                                                            <th>64</th>
                                                        </tr>
                                                        <tr>
                                                            <td>Employees In PDP</td>
                                                            <th>25</th>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="bg-themeGrayLight ">
                                            <div class="card-title">
                                                <h3>Learning and Development</h3>
                                            </div>
                                            <div class="table-responsive">
                                                <table class="table-lableNew  w-100">
                                                    <tbody>
                                                        <tr>
                                                            <td>Ongoing Training Programs</td>
                                                            <th><?php echo e($pending_trainings_count); ?></th>
                                                        </tr>
                                                        <tr>
                                                            <td>Completed Training Programs</td>
                                                            <th><?php echo e($completed_trainings_count); ?></th>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="bg-themeGrayLight ">
                                            <div class="card-title">
                                                <h3>Visa Management</h3>
                                            </div>
                                            <div class="table-responsive">
                                                <table class="table-lableNew  w-100">
                                                    <tbody>
                                                        <tr>
                                                            <td>Withdrawn</td>
                                                            <th>MVR <?php echo e($visa_withdraw_total); ?></th>
                                                        </tr>
                                                        <tr>
                                                            <td>Deposited</td>
                                                            <th>MVR <?php echo e($visa_deposited_total); ?></th>
                                                        </tr>
                                                        <tr>
                                                            <td>Reserved</td>
                                                            <th>MVR <?php echo e($visa_reserved_total); ?></th>
                                                        </tr>
                                                        <tr>
                                                            <td>Available</td>
                                                            <th>MVR <?php echo e($visa_available_total); ?></th>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="bg-themeGrayLight ">
                                            <div class="card-title">
                                                <h3>Leave</h3>
                                            </div>
                                            <div class="table-responsive">
                                                <table class="table-lableNew  w-100">
                                                    <tbody>
                                                        <tr>
                                                            <td>Total Applied Leaves</td>
                                                            <th><?php echo e($total_applied_leave); ?></th>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-4 col-sm-12">
                                <div class="row g-xxl-4 g-3">
                                    <div class="col-lg-12 col-sm-6">
                                        <div class="bg-themeGrayLight ">
                                            <div class="card-title">
                                                <h3>Accommodation</h3>
                                            </div>
                                            <div class="table-responsive">
                                                <table class="table-lableNew  w-100">
                                                    <tbody>
                                                        <tr>
                                                            <td>Total Bed</td>
                                                            <th><?php echo e($total_beds); ?></th>
                                                        </tr>
                                                        <tr>
                                                            <td>Occupied Bed</td>
                                                            <th><?php echo e($OccupiedBed); ?></th>
                                                        </tr>
                                                        <tr>
                                                            <td>Available Accommodation</td>
                                                            <th><?php echo e($total_available_beds); ?></th>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-12 col-sm-6">
                                        <div class="bg-themeGrayLight ">
                                            <div class="card-title">
                                                <h3>Survey</h3>
                                            </div>
                                            <div class="table-responsive">
                                                <table class="table-lableNew  w-100">
                                                    <tbody>
                                                        <tr>
                                                            <td>Total Surveys</td>
                                                            <th><?php echo e($total_survey_count); ?></th>
                                                        </tr>
                                                        <tr>
                                                            <td>Open Surveys</td>
                                                            <th><?php echo e($open_survey_count); ?></th>
                                                        </tr>
                                                        <tr>
                                                            <td>Pending Surveys</td>
                                                            <th><?php echo e($pending_survey_count); ?></th>
                                                        </tr>
                                                        <tr>
                                                            <td>Complete Surveys</td>
                                                            <th><?php echo e($complete_survey_count); ?></th>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-12 col-sm-6">
                                        <div class="bg-themeGrayLight ">
                                            <div class="card-title">
                                                <h3>Incident</h3>
                                            </div>
                                            <div class="table-responsive">
                                                <table class="table-lableNew  w-100">
                                                    <tbody>
                                                        <tr>
                                                            <td>Total Incidents</td>
                                                            <th><?php echo e($totalIncidentCounts); ?></th>
                                                        </tr>
                                                        <tr>
                                                            <td>Open Incidents</td>
                                                            <th><?php echo e($openIncidentCounts); ?></th>
                                                        </tr>
                                                        <tr>
                                                            <td>Under Investigation</td>
                                                            <th><?php echo e($underInvestigationIncidentCounts); ?></th>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-12 col-sm-6">
                                        <div class="bg-themeGrayLight ">
                                            <div class="card-title">
                                                <h3>People</h3>
                                            </div>
                                            <div class="table-responsive">
                                                <table class="table-lableNew  w-100">
                                                    <tbody>
                                                        <tr>
                                                            <td>Total Active Employee</td>
                                                            <th><?php echo e($total_employees); ?></th>
                                                        </tr>
                                                        <tr>
                                                            <td>Total New Hires</td>
                                                            <th><?php echo e($new_joining); ?></th>
                                                        </tr>
                                                        <tr>
                                                            <td>Expected Employees*</td>
                                                            <th>21</th>
                                                        </tr>
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
                            <div class="card card-talentAcqCompliances">
                                <div class="card-title">
                                    <div class="row g-md-2 g-1 align-items-center">
                                        <div class="col">
                                            <h3 class="text-nowrap">Compliance</h3>
                                        </div>
                                        <div class="col-auto">
                                            <a href="<?php echo e(route('people.compliance.index')); ?>" class="a-link">View All</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-12 col-md-6">
                            <div class="card card-talentAcqCompliances">
                                <div class="card-title">
                                    <div class="row g-md-2 g-1 align-items-center">
                                        <div class="col">
                                            <h3 class="text-nowrap">Calendar</h3>
                                        </div>
                                        <div class="col-auto">
                                            <a href="<?php echo e(route('people.compliance.Calendar')); ?>" class="a-link">View All</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-title">
                            <div class="row g-md-2 g-1 align-items-center">
                                <div class="col">
                                    <h3 class="text-nowrap">Requests *</h3>
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
                                                <div class="img-circle"><img src="assets/images/user-2.svg" alt="user">
                                                </div>
                                                <span class="userApplicants-btn">John Doe</span>
                                            </div>
                                        </td>
                                        <td>15 Mar 2025</td>
                                        <td><span class="badge badge-themeSuccess">Approved</span></td>
                                        <td>
                                            <a href="#" class="eye-btn skyBlue"><i class="fa-regular fa-eye"></i></a>
                                            <a href="#" class="close-btn"><i class="fa-solid fa-xmark"></i></a>
                                        </td>
                                    </tr>
                                    
                                    <tr>
                                        <td>#14521</td>
                                        <td>
                                            <div class="tableUser-block">
                                                <div class="img-circle"><img src="assets/images/user-2.svg" alt="user">
                                                </div>
                                                <span class="userApplicants-btn">John Doe</span>
                                            </div>
                                        </td>
                                        <td>15 Mar 2025</td>
                                        <td><span class="badge badge-themeYellow">Pending</span></td>
                                        <td>
                                            <a href="#" class="eye-btn skyBlue"><i class="fa-regular fa-eye"></i></a>
                                            <a href="#" class="correct-btn"><i class="fa-solid fa-check"></i></a>
                                            <a href="#" class="close-btn"><i class="fa-solid fa-xmark"></i></a>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card card-talentAcqRecentActivity">
                        <div class="card-title">
                            <div class="row g-md-2 g-1 align-items-center">
                                <div class="col">
                                    <h3 class="text-nowrap">Recent Activity *</h3>
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
                <div class="col-xl-6">
                    <div class="row g-3 g-xxl-4">
                        <div class="col-12">
                            <div class="card card-theme card-talentAcqWorkPlan">
                                <div class="card-title">
                                    <div class="row g-md-2 g-1 align-items-center">
                                        <div class="col">
                                            <h3 class="text-nowrap">WORKFORCE PLANNING</h3>
                                        </div>
                                        <div class="col-auto">
                                            <a href="<?php echo e(route('resort.workforceplan.dashboard')); ?>" class="a-link">View All</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="row g-xxl-3 g-2">
                                        <div class="col-sm-4">
                                            <div class="bg-themeGrayLight d-flex">
                                                <p>Total Budgeted Employees:</p>
                                                <strong> <?php echo e($manning_response->TotalBudgtedemp ?? '0'); ?></strong>
                                            </div>
                                        </div>
                                        <div class="col-sm-4 col-6">
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
                                        <div class="col-sm-4 col-6">
                                            <div class="bg-themeGrayLight d-flex">
                                                <p>Vacant</p>
                                                <strong class="fw-bold"><?php echo e($manning_response->total_vacant_count ?? '0'); ?></strong>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="bg-themeGrayLight">
                                                <div class="card  mb-30">
                                                    <div class="card-title d-flex justify-content-between">
                                                        <h3>Budget</h3>

                                                        <div class="form-group">
                                                            <select class="form-select form-select-sm " aria-label="Default select example">
                                                                <option value="1">2009-2012</option>
                                                                <option value="2">2013-2016</option>
                                                                <option value="3">2017-2020</option>
                                                                <option value="4" selected>2021-2024</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <canvas id="myBarChart"  width="273" height="206"  class="mb-2"></canvas>
                                                        <div class="row g-2 justify-content-center">
                                                            <div class="col-auto">
                                                                <div class="doughnut-label">
                                                                    <span class="bg-theme"></span>Budgeted
                                                                </div>
                                                            </div>
                                                            <div class="col-auto">
                                                                <div class="doughnut-label">
                                                                    <span class="bg-themeLightBlue"></span>Actual
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="bg-themeGrayLight">
                                               <div class="card  mb-30">
                                                    <div class="card-title d-flex justify-content-between">
                                                        <h3>Occupancy</h3>
                                                        <a href="#add-occupancymodal" data-bs-toggle="modal" class="btn-icon bg-green">
                                                            <i class="fa-solid fa-plus"></i>
                                                        </a>
                                                    </div>
                                                    <div class="text-center" id="occupancy-chart-slider">
                                                        <?php if($occupancies->isNotEmpty()): ?>
                                                            <?php $__currentLoopData = $occupancies; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $oc): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                                <div>
                                                                    <div>                                                                        <div class="d-flex justify-content-center date-slider">
                                                                            <a href="#" data-bs-toggle="tooltip" data-bs-placement="right" title="Tooltip on right"><?php echo e(date('d M Y',strtotime($oc->occupancydate))); ?></a>
                                                                        </div>
                                                                        <div class="pie my-3" style="--p:<?php echo e($oc->occupancyinPer); ?>;--green:#014653;--border:10px" data-bs-toggle="tooltip"
                                                                            data-bs-placement="right" title="<?php echo e($oc->occupancyinPer); ?>% Occupancy">
                                                                            <div>
                                                                                <strong class="d-block"> <?php echo e($oc->occupancyinPer); ?>%</strong>
                                                                                <span>Occupancy</span>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="card-fotter d-flex justify-content-between">
                                                                        <h4>Rooms Available:</h4>
                                                                        <?php 
                                                                            $availableRooms = $oc->occupancytotalRooms - $oc->occupancyOccupiedRooms; 
                                                                        ?>
                                                                        <label><?php echo e(($availableRooms)?  $availableRooms : 0); ?></label>
                                                                    </div>

                                                                </div>
                                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                        <?php else: ?>
                                                            <div>
                                                                <div>                                                                    <div class="d-flex justify-content-center date-slider">
                                                                        <a href="#" data-bs-toggle="tooltip" data-bs-placement="right" title="Tooltip on right"><?php echo e(date('d M Y')); ?></a>
                                                                    </div>
                                                                    <div class="pie my-3" style="--p:0;--green:#014653;--border:10px" data-bs-toggle="tooltip"
                                                                        data-bs-placement="right" title="0% Occupancy">
                                                                        <div>
                                                                            <strong class="d-block">0%</strong>
                                                                            <span>Occupancy</span>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="card-fotter d-flex justify-content-between">
                                                                    <h4>Rooms Available:</h4>
                                                                    <label>0</label>
                                                                </div>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-12 col-lg-6 ">
                            <div class="card card-theme ">
                                <div class="card-title">
                                    <div class="row justify-content-between align-items-center g-md-3 g-1">
                                        <div class="col">
                                            <h3>Attendance</h3>
                                        </div>
                                        <div class="col-auto">
                                            <div class="form-group">
                                                <select class="form-select YearWiseAttandance" aria-label="Default select example">
                                                    <?php
                                                        $currentYear = date('Y');

                                                        for ($i = 0; $i < 3; $i++) {
                                                            $startYear = $currentYear - $i;
                                                            $endYear = $startYear + 1;

                                                            echo "<option value=\"$startYear\"";

                                                            if ($i == 0)
                                                            {
                                                                echo " selected";
                                                            }

                                                            echo ">Jan $startYear - Dec $startYear</option>";
                                                        }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <canvas id="myAttendance"></canvas>
                            </div>
                        </div>
                        <div class="col-xl-12 col-lg-6 ">
                            <div class="card card-theme card-accomStati">
                                <div class="card-title">
                                    <div class="row g-md-2 g-1 align-items-center">
                                        <div class="col">
                                            <h3 class="text-nowrap">ACCOMMODATION</h3>
                                        </div>
                                        <div class="col-auto">
                                            <a href="#" class="a-link">View All</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="card-title">
                                        <h3>Accommodation Statistics</h3>
                                    </div>
                                    <div class="permissions-accordion" id="accordionPermissions">
                                        <?php $__currentLoopData = $buildings; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ak => $b): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>

                                            <?php
                                                $sanitizedGroupName = str_replace(' ', '', $ak);
                                                $isFirst = $loop->first;
                                            ?>

                                            <div class="accordion-item">
                                                <h2 class="accordion-header" id="headingOne<?php echo e($sanitizedGroupName); ?>">
                                                    <button class="accordion-button <?php echo e($isFirst ? '' : 'collapsed'); ?>"
                                                            type="button"
                                                            data-bs-toggle="collapse"
                                                            data-bs-target="#collapseOne<?php echo e($sanitizedGroupName); ?>"
                                                            aria-expanded="<?php echo e($isFirst ? 'true' : 'false'); ?>"
                                                            aria-controls="collapseOne<?php echo e($sanitizedGroupName); ?>">
                                                        <?php echo e($ak); ?>

                                                    </button>
                                                </h2>

                                                <div id="collapseOne<?php echo e($sanitizedGroupName); ?>"
                                                    class="accordion-collapse collapse <?php echo e($isFirst ? 'show' : ''); ?>"
                                                    aria-labelledby="headingOne<?php echo e($sanitizedGroupName); ?>"
                                                    data-bs-parent="#accordionPermissions">
                                                    <div class="accordion-body">
                                                        <?php if(array_key_exists(0,$b)): ?>
                                                        <?php $__currentLoopData = $b[0]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $name => $d): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                            <?php
                                                                $sanitizedName = str_replace(' ', '', $name);
                                                                $parts = explode('/', $d);
                                                                $numerator = (int) $parts[0];
                                                                $denominator = (int) $parts[1];
                                                                $percentage = $denominator > 0 ? ($numerator / $denominator) * 100 : 0; 
                                                            ?>

                                                            <div class="d-flex mb-3">
                                                                <div class="flex-grow-1">
                                                                    <span><?php echo e($name); ?></span>
                                                                    <div class="progress progress-custom progress-themeskyblue">
                                                                        <div class="progress-bar" role="progressbar" style="width: <?php echo e($percentage); ?>%;"
                                                                            aria-valuenow="<?php echo e($percentage); ?>" aria-valuemin="0" aria-valuemax="100">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div><?php echo e($numerator); ?>/<?php echo e($denominator); ?></div>
                                                            </div>
                                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="card card-theme card-talentAcqPayroll">
                                <div class="card-title">
                                    <div class="row g-md-2 g-1 align-items-center">
                                        <div class="col">
                                            <h3 class="text-nowrap">PAYROLL</h3>
                                        </div>
                                        <div class="col-auto">
                                            <a href="#" class="a-link">View All</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="row g-xxl-3 g-2">
                                        <div class="col-xxl-5 col-xl-12 col-md-5">
                                            <div class="bg-themeGrayLight">
                                                 
                                                <div class=" card-title">
                                                    <div class="row justify-content-between align-items-center g-md-2 g-1">
                                                        <div class="col">
                                                            <h3 class="text-nowrap">Payroll Expenses</h3>
                                                        </div>
                                                        <div class="col-auto">
                                                            <div class="form-group">
                                                                <select class="form-select YearWisePayrollExpense" id="yearFilter" aria-label="Default select example">
                                                                    <?php
                                                                    $currentYear = date('Y');
                                                                    for ($i = 0; $i < 3; $i++) {
                                                                        $startYear = $currentYear - $i;
                                                                        $endYear = $startYear + 1;
                                                                        echo "<option value=\"$startYear\"";
                                                                        if ($i == 0)
                                                                        {
                                                                            echo " selected";
                                                                        }
                                                                        echo ">Jan $startYear - Dec $startYear</option>";
                                                                    }
                                                                    ?>
                                                                </select>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <canvas id="myStackedBarChart" width="363" height="309" class="mb-3"></canvas>
                                                <div class="row g-2 ">
                                                    <div class="col-auto">
                                                        <div class="doughnut-label">
                                                            <span class="bg-theme"></span>Payroll Cost
                                                        </div>
                                                    </div>
                                                    <div class="col-auto">
                                                        <div class="doughnut-label">
                                                            <span class="bg-themeSkyblue"></span>OT Cost
                                                        </div>
                                                    </div>
                                                    <div class="col-auto">
                                                        <div class="doughnut-label">
                                                            <span class="bg-themeWarning"></span>Service Charge
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                            </div>
                                        </div>
                                        <div class="col-xxl-7 col-xl-12 col-md-7">
                                            <div class="bg-themeGrayLight">
                                                <div class=" card-title">
                                                    <div class="row justify-content-between align-items-center g-md-3 g-1">
                                                        <div class="col">
                                                            <h3 class="text-nowrap">Service Charges</h3>
                                                        </div>
                                                        <div class="col-auto">
                                                            <div class="form-group">
                                                                <select class="form-select YearWiseServichCharges" aria-label="Default select example">
                                                                    <?php
                                                                    $currentYear = date('Y');
                                                                    for ($i = 0; $i < 3; $i++) {
                                                                        $startYear = $currentYear - $i;
                                                                        $endYear = $startYear + 1;
                                                                        echo "<option value=\"$startYear\"";
                                                                        if ($i == 0)
                                                                        {
                                                                            echo " selected";
                                                                        }
                                                                        echo ">Jan $startYear - Dec $startYear</option>";
                                                                    }
                                                                    ?>
                                                                </select>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row g-4 align-items-center">
                                                    <div class="col-md-6">
                                                        <canvas id="myDoughnutChartService"></canvas>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="row g-2" id="myDoughnutChartServiceLabel"></div>
                                                    </div>
                                                </div>
                                                
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-12 col-lg-6">
                            <div class="card card-theme ">
                                <div class="card-title">
                                    <div class="row g-md-2 g-1 align-items-center">
                                        <div class="col">
                                            <h3 class="text-nowrap">LEARNING AND DEVELOPMENT *</h3>
                                        </div>
                                        <div class="col-auto">
                                            <a href="#" class="a-link">View All</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="card-title">
                                        <div class="row g-md-2 g-1 align-items-center">
                                            <div class="col">
                                                <h3 class="text-nowrap">Onboarding Training</h3>
                                            </div>
                                            <div class="col-auto">
                                                <select class="form-select form-select-sm"
                                                    aria-label="Default select example">
                                                    <option selected="">Percentage completion</option>
                                                    <option value="1">AAA</option>
                                                    <option value="2">AAA</option>
                                                </select>
                                            </div>
                                            <div class="col-auto">
                                                <select class="form-select form-select-sm"
                                                    aria-label="Default select example">
                                                    <option selected="">Department-wise</option>
                                                    <option value="1">AAA</option>
                                                    <option value="2">AAA</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row g-md-2 g-2 align-items-center">
                                        <div class="col-md-9"> <canvas id="onboardTrainChart" width="mb-2 w-100" height="293"
                                                class="mb-2"></canvas></div>
                                        <div class="col-md-3">
                                            <div class="row g-2 justify-content-center">
                                                <div class="col-lg-12 col-auto">
                                                    <div class="doughnut-label">
                                                        <span class="bg-theme"></span>Department 1
                                                    </div>
                                                </div>
                                                <div class="col-lg-12 col-auto">
                                                    <div class="doughnut-label">
                                                        <span class="bg-themeLightBlue"></span>Department 2
                                                    </div>
                                                </div>
                                                <div class="col-lg-12 col-auto">
                                                    <div class="doughnut-label">
                                                        <span class="bg-themeWarning"></span>Department 3
                                                    </div>
                                                </div>
                                                <div class="col-lg-12 col-auto">
                                                    <div class="doughnut-label">
                                                        <span class="bg-themeSkyblueLightNew"></span>Department 4
                                                    </div>
                                                </div>
                                                <div class="col-lg-12 col-auto">
                                                    <div class="doughnut-label">
                                                        <span class="bg-themeGray"></span>Department 5
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-12 col-lg-6">
                            <div class="card card-theme card-talentAcqIncident">
                                <div class="card-title">
                                    <div class="row g-md-2 g-1 align-items-center">
                                        <div class="col">
                                            <h3 class="text-nowrap">INCIDENT</h3>
                                        </div>
                                        <div class="col-auto">
                                            <a href="#" class="a-link">View All</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="card-title">
                                        <h3>Survey Status</h3>
                                    </div>
                                    <div class="row g-xxl-3 g-2 mb-3">
                                        <div class="col-sm-4">
                                            <div class="bg-themeGrayLight d-flex">
                                                <p>Minor</p>
                                                <strong><?php echo e($severityCounts['Minor'] ?? 0); ?></strong>
                                            </div>
                                        </div>
                                        <div class="col-sm-4 col-6">
                                            <div class="bg-themeGrayLight d-flex">
                                                <p>Moderate</p>
                                                <strong><?php echo e($severityCounts['Moderate'] ?? 0); ?></strong>
                                            </div>
                                        </div>
                                        <div class="col-sm-4 col-6">
                                            <div class="bg-themeGrayLight d-flex">
                                                <p>Severe</p>
                                                <strong><?php echo e($severityCounts['Severe'] ?? 0); ?></strong>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-title">
                                        <div class="row align-items-center g-2">
                                            <div class="col">
                                                <h3>Upcoming Meetings</h3>
                                            </div>
                                            <div class="col-auto"><a href="<?php echo e(route('incident.meeting')); ?>" class="a-link">View All</a></div>
                                        </div>
                                    </div>
                                    <div class="leaveUser-main" id="upcoming-meetings"></div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-12 col-lg-6">
                            <div class="card card-theme card-talentAcqSurvey">
                                <div class="card-title">
                                    <div class="row g-md-2 g-1 align-items-center">
                                        <div class="col">
                                            <h3 class="text-nowrap">SURVEY</h3>
                                        </div>
                                        <div class="col-auto">
                                            <a href="<?php echo e(route('Survey.Surveylist')); ?>" class="a-link">View All</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="card-title">
                                        <h3>Survey Status</h3>
                                    </div>
                                    <?php $__currentLoopData = $OngoingSurvey; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $survey): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <?php
                                            $progress = ($survey->total_count > 0) ? round(($survey->completed_count / $survey->total_count) * 100) : 0;
                                        ?>
                                        <div class="surveyStatus-block bg-themeGrayLight">
                                            <div class="head">
                                                <div>
                                                    <h6><?php echo e($survey->title); ?></h6>
                                                    <p>Creation Date: <?php echo e(\Carbon\Carbon::parse($survey->Start_date)->format('d M Y')); ?> | 
                                                        Closing Date: <?php echo e(\Carbon\Carbon::parse($survey->End_date)->format('d M Y')); ?></p>                                </div>
                                                <span class="badge badge-green">
                                                    <?php echo e($survey->Status); ?>

                                                </span>
                                            </div>
                                            <div class="body">
                                                <div class="d-flex">
                                                    <span>Participation Rate</span>
                                                    <div class="progress progress-custom progress-themeskyblue">
                                                        <div class="progress-bar" role="progressbar"   style="width: <?php echo e($progress); ?>%;" 
                                                        aria-valuenow="<?php echo e($progress); ?>"  aria-valuemin="0" aria-valuemax="100"></div>
                                                    </div>
                                                    <div><?php echo e($progress); ?>%</div>
                                                </div>
                                                <?php
                                                    $id = base64_encode($survey->id);
                                                    $view = route('Survey.view',$id);
                                                ?>     
                                                <div class="d-flex align-items-center">
                                                    <a target="_blank" href="<?php echo e($view); ?>" class="btn-tableIcon btnIcon-skyblue"><i
                                                            class="fa-regular fa-eye"></i></a>
                                                    <a href="javascript:void(0)" data-id="<?php echo e($id); ?>" class="SendNotifcation btn-tableIcon btnIcon-yellow"><i
                                                            class="fa-regular fa-bell"></i></a>
                                                    
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-12 col-lg-6">
                            <div class="card card-theme">
                                <div class="card-title">
                                    <div class="row g-md-2 g-1 align-items-center">
                                        <div class="col">
                                            <h3 class="text-nowrap">FILE MANAGEMENT *</h3>
                                        </div>
                                        <div class="col-auto">
                                            <a href="#" class="a-link">View All</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="card-title">
                                        <h3>Documents Expiring Soon</h3>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table-lableNew table-talentAcqDocExp w-100">
                                            <thead>
                                                <tr>
                                                    <th>Document Type</th>
                                                    <th>No. Of Document</th>
                                                    <th>Employee Name</th>
                                                    <th>Expiry Date</th>
                                                    <th>Days Left</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td>Visa</td>
                                                    <td>10</td>
                                                    <td>
                                                        <div class="user-ovImg user-ovImgTable">
                                                            <div class="img-circle">
                                                                <img src="assets/images/user-2.svg" alt="image">
                                                            </div>
                                                            <div class="img-circle">
                                                                <img src="assets/images/user-3.svg" alt="image">
                                                            </div>
                                                            <div class="img-circle">
                                                                <img src="assets/images/user-4.svg" alt="image">
                                                            </div>
                                                            <div class="img-circle">
                                                                <img src="assets/images/user-5.svg" alt="image">
                                                            </div>
                                                            <div class="img-circle">
                                                                <img src="assets/images/user-2.svg" alt="image">
                                                            </div>
                                                            <div class="img-circle">
                                                                <img src="assets/images/user-3.svg" alt="image">
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>15 Mar 2025</td>
                                                    <td>68 Days</td>
                                                </tr>
                                                <tr>
                                                    <td>Passport</td>
                                                    <td>8</td>
                                                    <td>
                                                        <div class="user-ovImg user-ovImgTable">
                                                            <div class="img-circle">
                                                                <img src="assets/images/user-2.svg" alt="image">
                                                            </div>
                                                            <div class="img-circle">
                                                                <img src="assets/images/user-3.svg" alt="image">
                                                            </div>
                                                            <div class="img-circle">
                                                                <img src="assets/images/user-4.svg" alt="image">
                                                            </div>
                                                            <div class="img-circle">
                                                                <img src="assets/images/user-5.svg" alt="image">
                                                            </div>
                                                            <div class="img-circle">
                                                                <img src="assets/images/user-2.svg" alt="image">
                                                            </div>
                                                            <div class="img-circle">
                                                                <img src="assets/images/user-3.svg" alt="image">
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>10 Feb 2025</td>
                                                    <td>32 Days</td>
                                                </tr>
                                                <tr>
                                                    <td>Contract</td>
                                                    <td>12</td>
                                                    <td>
                                                        <div class="user-ovImg user-ovImgTable">
                                                            <div class="img-circle">
                                                                <img src="assets/images/user-2.svg" alt="image">
                                                            </div>
                                                            <div class="img-circle">
                                                                <img src="assets/images/user-3.svg" alt="image">
                                                            </div>
                                                            <div class="img-circle">
                                                                <img src="assets/images/user-4.svg" alt="image">
                                                            </div>
                                                            <div class="img-circle">
                                                                <img src="assets/images/user-5.svg" alt="image">
                                                            </div>
                                                            <div class="img-circle">
                                                                <img src="assets/images/user-2.svg" alt="image">
                                                            </div>
                                                            <div class="img-circle">
                                                                <img src="assets/images/user-3.svg" alt="image">
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>20 Jan 2025</td>
                                                    <td>11 Days</td>
                                                </tr>
                                                <tr>
                                                    <td>Work Permit</td>
                                                    <td>15</td>
                                                    <td>
                                                        <div class="user-ovImg user-ovImgTable">
                                                            <div class="img-circle">
                                                                <img src="assets/images/user-2.svg" alt="image">
                                                            </div>
                                                            <div class="img-circle">
                                                                <img src="assets/images/user-3.svg" alt="image">
                                                            </div>
                                                            <div class="img-circle">
                                                                <img src="assets/images/user-4.svg" alt="image">
                                                            </div>
                                                            <div class="img-circle">
                                                                <img src="assets/images/user-5.svg" alt="image">
                                                            </div>
                                                            <div class="img-circle">
                                                                <img src="assets/images/user-2.svg" alt="image">
                                                            </div>
                                                            <div class="img-circle">
                                                                <img src="assets/images/user-3.svg" alt="image">
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>05 Apr 2025</td>
                                                    <td>86 Days</td>
                                                </tr>
                                                <tr>
                                                    <td>Visa</td>
                                                    <td>15</td>
                                                    <td>
                                                        <div class="user-ovImg user-ovImgTable">
                                                            <div class="img-circle">
                                                                <img src="assets/images/user-2.svg" alt="image">
                                                            </div>
                                                            <div class="img-circle">
                                                                <img src="assets/images/user-3.svg" alt="image">
                                                            </div>
                                                            <div class="img-circle">
                                                                <img src="assets/images/user-4.svg" alt="image">
                                                            </div>
                                                            <div class="img-circle">
                                                                <img src="assets/images/user-5.svg" alt="image">
                                                            </div>
                                                            <div class="img-circle">
                                                                <img src="assets/images/user-2.svg" alt="image">
                                                            </div>
                                                            <div class="img-circle">
                                                                <img src="assets/images/user-3.svg" alt="image">
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>15 Mar 2025</td>
                                                    <td>68 Days</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="card card-theme">
                                <div class="card-title">
                                    <div class="row g-md-2 g-1 align-items-center">
                                        <div class="col">
                                            <h3 class="text-nowrap">VISA MANAGEMENT *</h3>
                                        </div>
                                        <div class="col-auto">
                                            <a href="#" class="a-link">View All</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body pt-0">
                                    <div class="tab-theme visaManagtalentAcq-tab">
                                        <ul class="nav nav-tabs" id="myTab" role="tablist">
                                            <li class="nav-item" role="presentation">
                                                <button class="nav-link active" id="tab1" data-bs-toggle="tab"
                                                    data-bs-target="#tabPane1" type="button" role="tab"
                                                    aria-controls="tabPane1" aria-selected="true">Quota Slot
                                                    Fee</button>
                                            </li>
                                            <li class="nav-item" role="presentation">
                                                <button class="nav-link" id="#tab2" data-bs-toggle="tab"
                                                    data-bs-target="#tabPane2" type="button" role="tab"
                                                    aria-controls="tabPane2" aria-selected="false">Work Permit
                                                    Fee</button>
                                            </li>
                                            <li class="nav-item" role="presentation">
                                                <button class="nav-link" id="tab3" data-bs-toggle="tab"
                                                    data-bs-target="#tabPane3" type="button" role="tab"
                                                    aria-controls="tabPane3" aria-selected="false">Insurance</button>
                                            </li>
                                            <li class="nav-item" role="presentation">
                                                <button class="nav-link" id="tab4" data-bs-toggle="tab"
                                                    data-bs-target="#tabPane4" type="button" role="tab"
                                                    aria-controls="tabPane4" aria-selected="true">Work Permit Medical
                                                    Fee</button>
                                            </li>
                                        </ul>
                                        <div class="tab-content" id="myTabContent">
                                            <div class="tab-pane fade show active" id="tabPane1" role="tabpanel"
                                                aria-labelledby="tab1" tabindex="0">
                                                <div class="row g-xl-4 g-md-3 g-2 mb-2 align-items-center ">
                                                    <div class="col"><input type="text"
                                                            class="form-control form-control-small datepicker"
                                                            placeholder="Select Duration"></div>
                                                    <div class="col-auto"><a href="#" class="a-link">View all</a></div>
                                                </div>
                                                <div class="row  g-md-4 g-3 mb-md-4 mb-3">
                                                    <div class="col-md-5">
                                                        <div class="talentAcqVisaTotal-box">
                                                            <div
                                                                class="d-flex justify-content-between align-items-center">
                                                                <label>Total Xpats:</label>
                                                                <span>142</span>
                                                            </div>
                                                            <div
                                                                class="d-flex justify-content-between align-items-center">
                                                                <label>Total Paid:</label>
                                                                <span>$150,000</span>
                                                            </div>
                                                            <div
                                                                class="d-flex justify-content-between align-items-center">
                                                                <label>Today:</label>
                                                                <span>$5,000</span>
                                                            </div>
                                                            <div
                                                                class="d-flex justify-content-between align-items-center">
                                                                <label>This Week:</label>
                                                                <span>$15,000</span>
                                                            </div>
                                                            <div
                                                                class="d-flex justify-content-between align-items-center">
                                                                <label>This Month:</label>
                                                                <span>$45,000</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-7">
                                                        <div class="bg-themeGrayLight   h-100">
                                                            <h6 class="mb-2">Overdue Alerts</h6>
                                                            <div
                                                                class="user-block block-danger  mb-1 d-flex align-items-center">
                                                                <div class="img-circle">
                                                                    <img src="assets/images/user-2.svg" alt="image">
                                                                </div>
                                                                <div
                                                                    class="w-100 d-xxl-flex d-xl-inline d-sm-flex align-items-center justify-content-between">
                                                                    <div>
                                                                        <h6>Elina Willson<span>#34523</span></h6>
                                                                        <p>F&amp;B - Sheaf</p>
                                                                    </div>
                                                                    <div
                                                                        class="overdue-text text-end mt-xxl-0 mt-xl-1 mt-sm-0 mt-2">
                                                                        5
                                                                        days overdue</div>
                                                                </div>
                                                            </div>
                                                            <div
                                                                class="user-block block-danger  d-flex align-items-center">
                                                                <div class="img-circle">
                                                                    <img src="assets/images/user-3.svg" alt="image">
                                                                </div>
                                                                <div
                                                                    class="w-100 d-xxl-flex d-xl-inline d-sm-flex align-items-center justify-content-between">
                                                                    <div>
                                                                        <h6>Sean Sen<span>#34524</span></h6>
                                                                        <p>F&amp;B - Sheaf</p>

                                                                    </div>

                                                                    <div
                                                                        class="overdue-text text-end mt-xxl-0 mt-xl-1 mt-sm-0 mt-2">
                                                                        5
                                                                        days overdue</div>

                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="bg-themeGrayLight mb-3">
                                                    <div class="card-title">
                                                        <h3>Expiry Dates Overview</h3>
                                                    </div>
                                                    <h6 class="mb-2">Work Permit</h6>
                                                    <div class="user-block d-flex align-items-center mb-2">
                                                        <div class="img-circle">
                                                            <img src="assets/images/user-2.svg" alt="image">
                                                        </div>
                                                        <div
                                                            class="w-100 d-flex align-items-center justify-content-between flex-wrap gap-1">
                                                            <div>
                                                                <h6>Addey Willson<span>#34523</span></h6>
                                                                <p>F&amp;B - Sheaf</p>
                                                            </div>
                                                            <div class="overdue-text">Expires: 15 March 2025</div>
                                                        </div>
                                                    </div>
                                                    <div class="user-block d-flex align-items-center mb-3">
                                                        <div class="img-circle">
                                                            <img src="assets/images/user-5.svg" alt="image">
                                                        </div>
                                                        <div
                                                            class="w-100 d-flex align-items-center justify-content-between flex-wrap gap-1">
                                                            <div>
                                                                <h6>Addey Willson<span>#34523</span></h6>
                                                                <p>F&amp;B - Sheaf</p>
                                                            </div>
                                                            <div class="overdue-text">Expires: 15 March 2025</div>
                                                        </div>
                                                    </div>
                                                    <h6 class="mb-2">insurance</h6>
                                                    <div class="user-block    d-flex align-items-center">
                                                        <div class="img-circle">
                                                            <img src="assets/images/user-5.svg" alt="image">
                                                        </div>
                                                        <div
                                                            class="w-100 d-flex align-items-center justify-content-between flex-wrap gap-1">
                                                            <div>
                                                                <h6>Addey Willson<span>#34523</span></h6>
                                                                <p>F&amp;B - Sheaf</p>
                                                            </div>
                                                            <div class="overdue-text">Expires: 15 March 2025</div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="card-title">
                                                    <div class="row g-2 align-items-center">
                                                        <div class="col">
                                                            <h3 class="text-nowrap">Deposit Refund Requests</h3>
                                                        </div>
                                                        <div class="col-auto"><select class="form-select"
                                                                aria-label="Default select example">
                                                                <option selected="">Select Position</option>
                                                                <option value="1">AAA</option>
                                                                <option value="2">AAA</option>
                                                            </select></div>
                                                        <div class="col-auto"><select class="form-select"
                                                                aria-label="Default select example">
                                                                <option selected="">Select Dates</option>
                                                                <option value="1">AAA</option>
                                                                <option value="2">AAA</option>
                                                            </select></div>
                                                    </div>
                                                </div>
                                                <div class="table-responsive">
                                                    <table id="" class="table table-talentAcqDepositRefundReq  w-100 ">
                                                        <thead>
                                                            <tr>
                                                                <th>ID</th>
                                                                <th>Employee Name</th>
                                                                <th>Nationality</th>
                                                                <th>Deposit Amount</th>
                                                                <th>Current Wallet</th>

                                                                <th>Status</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <tr>
                                                                <td>#6787</td>
                                                                <td>
                                                                    <div class="tableUser-block">
                                                                        <div class="img-circle "><img
                                                                                src="assets/images/user-2.svg"
                                                                                alt="user">
                                                                        </div>
                                                                        <span class="userApplicants-btn">John Doe</span>
                                                                    </div>
                                                                </td>
                                                                <td>Indian</td>
                                                                <td>$5,000</td>
                                                                <td>$5,000</td>

                                                                <td><span class="badge badge-themeBlue">Requested</span>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td>#451258</td>
                                                                <td>
                                                                    <div class="tableUser-block">
                                                                        <div class="img-circle "><img
                                                                                src="assets/images/user-2.svg"
                                                                                alt="user">
                                                                        </div>
                                                                        <span class="userApplicants-btn">Christian
                                                                            Slater</span>
                                                                    </div>
                                                                </td>
                                                                <td>Filipino</td>
                                                                <td>$5,000</td>
                                                                <td>$5,000</td>

                                                                <td><span class="badge badge-themeSkyblue">Not
                                                                        Requested</span></td>
                                                            </tr>
                                                            <tr>
                                                                <td>#745125</td>
                                                                <td>
                                                                    <div class="tableUser-block">
                                                                        <div class="img-circle "><img
                                                                                src="assets/images/user-2.svg"
                                                                                alt="user">
                                                                        </div>
                                                                        <span class="userApplicants-btn">Brijesh
                                                                            Pandey</span>
                                                                    </div>
                                                                </td>
                                                                <td>Indian</td>
                                                                <td>$5,000</td>
                                                                <td>$5,000</td>

                                                                <td><span class="badge badge-themeBlue">Requested</span>
                                                                </td>

                                                            </tr>
                                                            <tr>
                                                                <td>#784512</td>
                                                                <td>
                                                                    <div class="tableUser-block">
                                                                        <div class="img-circle "><img
                                                                                src="assets/images/user-2.svg"
                                                                                alt="user">
                                                                        </div>
                                                                        <span class="userApplicants-btn">Seerish
                                                                            Yadav</span>
                                                                    </div>
                                                                </td>
                                                                <td>Indian</td>
                                                                <td>$5,000</td>
                                                                <td>$5,000</td>

                                                                <td><span class="badge badge-themeSkyblue">Not
                                                                        Requested</span></td>

                                                            </tr>
                                                            <tr>
                                                                <td>#784525</td>
                                                                <td>
                                                                    <div class="tableUser-block">
                                                                        <div class="img-circle "><img
                                                                                src="assets/images/user-2.svg"
                                                                                alt="user">
                                                                        </div>
                                                                        <span class="userApplicants-btn">John Doe</span>
                                                                    </div>
                                                                </td>
                                                                <td>Filipino</td>
                                                                <td>$5,000</td>
                                                                <td>$5,000</td>

                                                                <td><span class="badge badge-themeBlue">Requested</span>
                                                                </td>

                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>

                                            </div>
                                            <div class="tab-pane fade" id="tabPane2" role="tabpanel"
                                                aria-labelledby="#tab2" tabindex="0">
                                                Work Permit Fee
                                            </div>
                                            <div class="tab-pane fade" id="tabPane3" role="tabpanel"
                                                aria-labelledby="tab3" tabindex="0">
                                                Insurance
                                            </div>
                                            <div class="tab-pane fade" id="tabPane4" role="tabpanel"
                                                aria-labelledby="tab4" tabindex="0">
                                                Work Permit Medical Fee
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-6">
                    <div class="row g-3 g-xxl-4">
                        <div class="col-12">
                            <div class="card card-theme card-talentAcqTalentAcq">
                                <div class="card-title">
                                    <div class="row g-md-2 g-1 align-items-center">
                                        <div class="col">
                                            <h3 class="text-nowrap">TALENT ACQUISITION</h3>
                                        </div>
                                        <div class="col-auto">
                                            <a href="<?php echo e(route('resort.recruitement.hrdashboard')); ?>" class="a-link">View All</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="row g-md-3 g-2">
                                        <div class="col-12">
                                            <div class="bg-themeGrayLight">
                                                <div class="card-title">
                                                    <div class="row g-md-2 g-1 align-items-center">
                                                        <div class="col">
                                                            <h3 class="text-nowrap">Vacancies</h3>
                                                        </div>
                                                        <div class="col-auto">
                                                            <a href="<?php echo e(route('resort.vacancies.FreshApplicant')); ?>" class="a-link">View all</a>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="table-responsive">
                                                    <table class="table table-collapse table-vacRec">
                                                        <thead>
                                                            <tr>
                                                                <th>Positions</th>
                                                                <th>Department</th>
                                                                <th>No. of Vacancy</th>
                                                                <th>No. of Applicant</th>
                                                                <th>Action</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php if(isset($NewVacancies) && $NewVacancies->isNotEmpty()): ?>
                                                                <?php $__currentLoopData = $NewVacancies; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $vac): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                                    <tr>
                                                                        <td> <?php echo e($vac->positionTitle); ?>

                                                                            <span class="badge badge-themeLight"> <?php echo e($vac->PositonCode); ?> </span>
                                                                        </td>
                                                                        <td> <?php echo e($vac->Department); ?> <span class="badge badge-themeLight"> <?php echo e($vac->DepartmentCode); ?></span></td>
                                                                        <td><?php echo e($vac->NoOfVacnacy); ?></td>
                                                                        <td><?php echo e($vac->NoOfApplication); ?></td>
                                                                        <td><a href="<?php echo e(route("resort.ta.Applicants",    base64_encode($vac->vacancy_id))); ?>" class="eye-btn"><i class="fa-regular fa-eye"></i></a>
                                                                        </td>
                                                                    </tr>
                                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                            <?php endif; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="bg-themeGrayLight">
                                                <div class="card-title">
                                                    <h3>To Do List</h3>
                                                </div>
                                                <div class="todoList-main">

                                                <?php if(isset($TodoData) && $TodoData->isNotEmpty()): ?>

                                                    <?php $__currentLoopData = $TodoData; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $t): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>

                                                        <div class="todoList-block">
                                                            <?php if(!isset($t->ApplicantID) ): ?>
                                                                <div class="img-circle">
                                                                    <img src="<?php echo e(Common::getResortUserPicture($t->user_id)); ?>" alt="image">
                                                                </div>
                                                                <div>

                                                                    <p><?php echo e($t->rank_name); ?> Is Approved Vacancy For <?php echo e($t->Position ?? ''); ?> </p>
                                                                    <?php if($t->LinkShareOrNot =="No"): ?>
                                                                        <a  href="<?php echo e(route('resort.ta.add.Questionnaire')); ?>"
                                                                        target="_blank"
                                                                        class="a-link">Before You Create  Job Advertisement You must be add Questioners</a>


                                                                    <?php else: ?>
                                                                    <a  href="javascript:void(0)"
                                                                        data-Resort_id="<?php echo e($t->Resort_id); ?>"
                                                                        data-ta_childid="<?php echo e($t->ta_childid); ?>"
                                                                        data-ExpiryDate ="<?php echo e($t->ExpiryDate); ?>" data-jobadvertisement="<?php echo e($t->JobAdvertisement); ?>" data-link="<?php echo e($t->adv_link); ?>"  data-applicationUrlshow="<?php echo e($t->applicationUrlshow); ?>" data-applicant_link="<?php echo e($t->applicant_link); ?>"
                                                                        data-source_links="<?php echo e(json_encode($t->source_links)); ?>" data-bs-toggle="modal" class="a-link jobAD-modal">Create Job Advertisement</a>

                                                                    <?php endif; ?>
                                                                    </div>
                                                                

                                                                <?php elseif( $t->ApplicationStatus=="Sortlisted" &&  $t->As_ApprovedBy == 3  &&  $t->InterviewLinkStatus == null ): ?>
                                                                    <div class="img-circle">
                                                                        <img src="<?php echo e($t->profileImg); ?>" alt="image">
                                                                    </div>
                                                                    <div>
                                                                        <p><?php echo e(ucfirst($t->first_name).'  '.ucfirst($t->last_name)); ?> Is Shortlisted f <?php echo e($t->Position ?? ''); ?> </p>
                                                                        <a
                                                                        href="javascript:void(0)"
                                                                        data-Resort_id="<?php echo e($t->Resort_id); ?>"
                                                                        data-ApplicantID="<?php echo e(base64_encode($t->ApplicantID)); ?>"
                                                                        data-ApplicantStatus_id="<?php echo e(base64_encode($t->ApplicantStatus_id)); ?>"
                                                                        class="a-link SortlistedEmployee">Send Interview Request </a>
                                                                    </div>

                                                            <?php endif; ?>
                                                        </div>
                                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                <?php else: ?>
                                                    <div>
                                                        <p>No Data Reacord</p>

                                                    </div>
                                                <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="bg-themeGrayLight">
                                                <div class="card-title">
                                                    <h3>New Hire Requests</h3>
                                                </div>
                                                <div class="hireReq-main"  id="FreshHiringRequest">
                                                    <?php if(isset($Vacancies) &&  $Vacancies->count() > 0): ?>
                                                        <?php $__currentLoopData = $Vacancies; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $vacancy): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                            <div class="hireReq-block">
                                                                <div class="img-circle">
                                                                    <img src="<?php echo e(Common::getResortUserPicture($vacancy->resort_id)); ?>" alt="image">
                                                                </div>
                                                                <div>
                                                                    <h6><?php echo e($vacancy->Department); ?> (<?php echo e($vacancy->rank_name); ?>)  </h6>
                                                                    <p>Requested for Hire <?php echo e($vacancy->NoOfVacnacy); ?> <?php echo e($vacancy->Position ?? 'Position'); ?></p>
                                                                    
                                                                </div>
                                                                <div class="icon">
                                                                    <a href="javascript:void(0)" class="respondOfFreshmodal"
                                                                            data-images="<?php echo e(Common::getResortUserPicture($vacancy->resort_id)); ?>"
                                                                            data-ta_id="<?php echo e($vacancy->ta_id); ?>"
                                                                            data-departmentName="<?php echo e($vacancy->Department); ?>"
                                                                            data-rank="<?php echo e($vacancy->rank_name); ?>"
                                                                            data-position="<?php echo e($vacancy->Position); ?>"
                                                                            data-NoOfVacnacy="<?php echo e($vacancy->NoOfVacnacy); ?>"
                                                                            data-Child_ta_id ="<?php echo e($vacancy->Child_ta_id); ?>">
                                                                            Respond
                                                                    </a>

                                                                </div>
                                                            </div>
                                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                    <?php else: ?>
                                                        <p>No new hire requests available.</p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="card card-theme card-talentAcqLeave">
                                <div class="card-title">
                                    <div class="row g-md-2 g-1 align-items-center">
                                        <div class="col">
                                            <h3 class="text-nowrap">LEAVE</h3>
                                        </div>
                                        <div class="col-auto">
                                            <a href="#" class="a-link">View All</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="row g-md-3 g-2">
                                        <div class="col-sm-6">
                                            <div class="bg-themeGrayLight">
                                                <div class="card-title">
                                                    <div class="row g-md-2 g-1 align-items-center">
                                                        <div class="col">
                                                            <h3 class="text-nowrap">Who's On Leave</h3>
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
                                        <div class="col-sm-6">
                                            <div class="bg-themeGrayLight">
                                                <div class="card-title">
                                                    <div class="row g-md-2 g-1 align-items-center">
                                                        <div class="col">
                                                            <h3 class="text-nowrap">Upcoming Leaves</h3>
                                                        </div>
                                                        <div class="col-auto">
                                                            
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
                                        <div class="col-sm-6">
                                            <div class="bg-themeGrayLight card-talentAcqLeaveUpcoming">
                                                <div class="card-title">
                                                    <h3>Upcoming Public Holidays</h3>
                                                </div>
                                                <div class="leaveUser-main">
                                                    <?php $__currentLoopData = $upcommingPublicHoliday; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $holiday): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                        <div class="leaveUser-bgBlock bg-white">
                                                            <h6><?php echo e($holiday->name); ?></h6>
                                                            <p><?php echo e(Carbon\Carbon::parse($holiday->holiday_date)->format('d M, D')); ?></p>
                                                        </div>
                                                   <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                    
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="bg-themeGrayLight card-upcomingBirthLeve ">
                                                <div class="card-title">
                                                    <h3>Upcoming Birthdays</h3>
                                                </div>
                                                <div class="leaveUser-main">
                                                    <?php if($todayBirthdays->count() >0): ?>
                                                        <div class="leaveUser-bgBlock">
                                                            <h6>Today</h6>
                                                        </div>
                                                        <?php $__currentLoopData = $todayBirthdays; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $birthdayEmployee): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?> 
                                                        
                                                            <div class="leaveUser-block">
                                                                <div class="img-circle">
                                                                    <img src="<?php echo e(App\Helpers\Common::getResortUserPicture($birthdayEmployee->Admin_Parent_id)); ?>" alt="image">
                                                                </div>
                                                                <div>
                                                                    <h6><?php echo e($birthdayEmployee->resortAdmin->full_name); ?></h6>
                                                                    <p><?php echo e($birthdayEmployee->department->name); ?> - <?php echo e($birthdayEmployee->position->position_title); ?></p>
                                                                    <div class="d-flex">
                                                                        <a href="#" class="a-linkTheme">Send Message</a>
                                                                        <a href="<?php echo e(route('resort.recruitement.send.birthday-notification',$birthdayEmployee->id)); ?>" class="a-link">Notify All Employees</a>
                                                                    </div>
                                                                    <span class="badge badge-themeNew1"><i class="fa-regular fa-cake-candles"></i> <?php echo e(Carbon\Carbon::parse($birthdayEmployee->dob)->format('d-M')); ?></span>
                                                                </div>
                                                            </div>
                                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                    <?php endif; ?>
                                                    <?php if($upcommingBirthdays->count() > 0): ?>
                                                        <div class="leaveUser-bgBlock">
                                                            <h6>Tomorrow</h6>
                                                        </div>
                                                        <?php $__currentLoopData = $upcommingBirthdays; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $birthdayEmployee): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                            <div class="leaveUser-block">
                                                                <div class="img-circle">
                                                                    <img src="<?php echo e(App\Helpers\Common::getResortUserPicture($birthdayEmployee->Admin_Parent_id)); ?>" alt="image">
                                                                </div>
                                                                <div>
                                                                    <h6><?php echo e($birthdayEmployee->resortAdmin->full_name); ?></h6>
                                                                    <p><?php echo e($birthdayEmployee->department->name); ?> - <?php echo e($birthdayEmployee->position->position_title); ?></p>
                                                                    <span class="badge badge-themeNew1"><i class="fa-regular fa-cake-candles"></i> <?php echo e(Carbon\Carbon::parse($birthdayEmployee->dob)->format('d-M')); ?></span>
                                                                </div>
                                                            </div>
                                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                    <?php endif; ?>
                                                </div>
                                                
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="bg-themeGrayLight">
                                                <div class="card-title">
                                                    <div class="row g-xl-3 g-md-2 g-1 align-items-center">
                                                        <div class="col">
                                                            <h3 class="text-nowrap">Leave Requests</h3>
                                                        </div>
                                                        <div class="col-auto">
                                                            <select id="department-filter" class="form-select select2t-none" aria-label="Default select example">
                                                                <option value="">All Departments</option>
                                                                <?php if($resort_departments): ?>
                                                                    <?php $__currentLoopData = $resort_departments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $dept): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                                        <option value="<?php echo e($dept->id); ?>"><?php echo e($dept->name); ?></option>
                                                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                                <?php endif; ?>
                                                            </select>
                                                        </div>
                                                        <div class="col-auto"><a href="<?php echo e(route('leave.dashboard')); ?>" class="a-link">View All</a>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="table-responsive">
                                                    <table id="leave-request-table" class="table table-leaveReq w-100 mb-1">
                                                        <thead>
                                                            <tr>
                                                                <th>Employee ID</th>
                                                                <th>Employee Name</th>
                                                                <th>Total Days</th>
                                                                <th>Status</th>
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
                        <div class="col-xl-12 col-lg-6 order-lg-0 order-xl-0">
                            <div class="card card-theme">
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
                                        <div class="row g-md-2 g-1 align-items-center">
                                            <div class="col">
                                                <h3 class="text-nowrap">Grievances</h3>
                                            </div>
                                            <div class="col-auto">
                                                <a href="<?php echo e(route('GrievanceAndDisciplinery.grivance.GrivanceIndex')); ?>" class="a-link">View All</a>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row g-md-4 g-2">
                                        <?php $__currentLoopData = $grivanceSubmissionModel; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $grievance): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <div class="col-sm-6">
                                                <div class="d-flex justify-content-between mb-2 border-bottom pb-2">
                                                    <p class="mb-0"><?php echo e($grievance->category_name); ?></p>
                                                    <p><?php echo e($grievance->count); ?></p>
                                                </div>
                                            </div>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 order-lg-2 order-xl-0">
                            <div class="card card-theme card-talentAcqPerformance ">
                                <div class="card-title">
                                    <div class="row g-md-2 g-1 align-items-center">
                                        <div class="col">
                                            <h3 class="text-nowrap">PERFORMANCE *</h3>
                                        </div>
                                        <div class="col-auto">
                                            <a href="#" class="a-link">View All</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="card-title">
                                        <div class="row g-md-2 g-1 align-items-center">
                                            <div class="col">
                                                <h3 class="text-nowrap">Appraisal Pending Departments </h3>
                                            </div>
                                            <div class="col-auto">
                                                <a href="#" class="a-link">View All</a>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="table-responsive mb-md-4 mb-3">
                                        <table id="" class="table  w-100 mb-1">
                                            <thead>
                                                <tr>
                                                    <th>Department</th>
                                                    <th>Appraisal Time</th>
                                                    <th>Employees</th>
                                                    <th>Last Appraisal</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td>Management <span class="badge badge-themeLight">M-415</span>
                                                    </td>
                                                    <td>6 month</td>
                                                    <td>15</td>
                                                    <td>1 oct 2019</td>
                                                    <td><span class="badge badge-themeYellow">Pending</span></td>
                                                </tr>
                                                <tr>
                                                    <td>Management <span class="badge badge-themeLight">M-415</span>
                                                    </td>
                                                    <td>6 month</td>
                                                    <td>10</td>
                                                    <td>5 July 2020</td>
                                                    <td><span class="badge badge-themeSuccess">Done</span></td>
                                                </tr>
                                                <tr>
                                                    <td>Front Office <span class="badge badge-themeLight">F-845</span>
                                                    </td>
                                                    <td>6 month</td>
                                                    <td>50</td>
                                                    <td>20 Jun 2018</td>
                                                    <td><span class="badge badge-themeSuccess">Done</span></td>
                                                </tr>
                                                <tr>
                                                    <td>Housekeeping <span class="badge badge-themeLight">H-451</span>
                                                    </td>
                                                    <td>6 month</td>
                                                    <td>26</td>
                                                    <td>3 Aug 2022</td>
                                                    <td><span class="badge badge-themeSuccess">Done</span></td>
                                                </tr>
                                                <tr>
                                                    <td>Management <span class="badge badge-themeLight">M-515</span>
                                                    </td>
                                                    <td>6 month</td>
                                                    <td>18</td>
                                                    <td>7 Jan 2023</td>
                                                    <td><span class="badge badge-themeSuccess">Done</span></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="bg-themeGrayLight">
                                        <div class="card-title">
                                            <div class="row g-md-2 g-1 align-items-center">
                                                <div class="col">
                                                    <h3 class="text-nowrap">Monthly Check-In *</h3>
                                                </div>
                                                <div class="col-auto">
                                                    <a href="#" class="a-link">View All</a>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="overflow-auto pe-1">
                                            <?php $__currentLoopData = $monthlyCheckinPerformance; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $checkin): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <div class="monthlyCheck-block">
                                                <div class="img-circle  userImg-block "><img src="<?php echo e(Common::getResortUserPicture($checkin->employee->Admin_Parent_id)); ?>"
                                                        alt="user">
                                                </div>
                                                <div class="w-100">
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
                        <div class="col-xl-12 col-lg-6  order-lg-1 order-xl-0">
                            <div class="card card-theme">
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
                                    <div class="card-title">
                                        <div class="row g-md-2 g-1 align-items-center">
                                            <div class="col">
                                                <h3 class="text-nowrap">Recent SOS</h3>
                                            </div>
                                            <div class="col-auto">
                                                <a href="<?php echo e(route('sos.dashboard.index')); ?>" class="a-link">View All</a>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="table-responsive">
                                        <table id="" class="table  table-sosHistory w-100 mb-1">
                                            <thead>
                                                <tr>
                                                    <th>SOS Type</th>
                                                    <th>Initiated By</th>
                                                    <th>Location</th>
                                                    <th>Date & Time</th>
                                                    <th>Status</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php $__currentLoopData = $SOSHistory; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $history): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <tr>
                                                    <td><?php echo e($history->getSos->name); ?></td>
                                                    <td>
                                                        <div class="tableUser-block">
                                                            <div class="img-circle"><img src="<?php echo e(Common::getResortUserPicture($history->employee->Admin_Parent_id)); ?>"
                                                                    alt="user">
                                                            </div>
                                                            <span class="userApplicants-btn"><?php echo e($history->employee->resortAdmin->full_name); ?></span>
                                                        </div>
                                                    </td>
                                                    <td><?php echo e($history->location); ?></td>
                                                    <td><?php echo e($history->date); ?></td>
                                                    <td><span class="badge badge-themeSuccess"><?php echo e($history->status); ?></span></td>
                                                    <td>
                                                        <a href="<?php echo e(route('sos.emergency.view', base64_encode($history->id))); ?>" class="btn-lg-icon icon-bg-skyblue">
                                                            <i class="fa fa-eye"></i>
                                                        </a>
                                                        <a href="<?php echo e(route('sos.showMap', base64_encode($history->id))); ?>" class="btn-lg-icon icon-bg-blue">
                                                            <i class="fa fa-location"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-12  order-lg-3 order-xl-0">
                            <div class="card card-theme card-talentAcqPeople">
                                <div class="card-title">
                                    <div class="row g-md-2 g-1 align-items-center">
                                        <div class="col">
                                            <h3 class="text-nowrap">PEOPLE</h3>
                                        </div>
                                        <div class="col-auto">
                                            <a href="<?php echo e(route('people.hr.dashboard')); ?>" class="a-link">View All</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="row  g-md-3 g-2">
                                        <div class="col-sm-6">
                                            <div class="bg-themeGrayLight h-100">
                                                <div class="card-title">
                                                    <h3>Employee Type</h3>
                                                </div>
                                                <div class="incident-chart  mb-2">
                                                   <canvas id="myDoughnutChart" data-male="<?php echo e($male_emp); ?>" data-female="<?php echo e($female_emp); ?>"></canvas>
                                                </div>
                                                <div class="row g-2 justify-content-center mb-md-3 mb-2">
                                                    <div class="col-auto">
                                                        <div class="doughnut-label">
                                                            <span class="bg-theme"></span>Male
                                                        </div>
                                                    </div>
                                                    <div class="col-auto">
                                                        <div class="doughnut-label">
                                                            <span class="bg-themeSkyblue"></span>Female
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="d-flex align-items-center  mb-lg-2 mb-1">
                                                    <div
                                                        class="progress progress-custom progress-themeBlue flex-grow-1 me-2">
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
                                        <div class="col-sm-6">
                                            <div class="bg-themeGrayLight mb-md-3 mb-2">
                                                <div class="card-title">
                                                    <h3>Announcements</h3>
                                                </div>
                                                <div class="leaveUser-main">
                                                    <div class="leaveUser-bgBlock">
                                                        <h6>Total Announcements Published</h6>
                                                          <strong><?php echo e($totalPublished); ?></strong>
                                                    </div>
                                                    <div class="table-responsive">
                                                        <table class="table-lableNew table-totalAnnSPeopleEmp w-100">
                                                            <tbody>
                                                                <tr>
                                                                    <td>Employee Of The Month</td>
                                                                    <th>01</th>
                                                                </tr>
                                                                <tr>
                                                                    <td>Supervisor Of The Quarter</td>
                                                                    <th>02</th>
                                                                </tr>
                                                                <tr>
                                                                    <td>Manager Of The Quarter</td>
                                                                    <th>01</th>
                                                                </tr>
                                                                <tr>
                                                                    <td>Special Recognition</td>
                                                                    <th>01</th>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="bg-themeGrayLight text-center viewOrgChart ">
                                                <a href="<?php echo e(route('people.org-chart')); ?>" class="fw-600">View Organization Chart</a>
                                            </div>
                                        </div>
                                        <div class="col-sm-8">
                                            <div class="card-title">
                                                <h3>Info Update Requests</h3>
                                            </div>
                                            <div class="leaveUser-main">
                                                <?php $__currentLoopData = $employeeInfoUpdateRequest; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $emp_info): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                    <?php
                                                        $profilePicture = App\Helpers\Common::GetAdminResortProfile($emp_info->employee->Admin_Parent_id);
                                                    ?>
                                                    <div class="leaveUser-block">
                                                            <div class="img-circle">
                                                                <img src="<?php echo e($profilePicture); ?>" alt="user" class="img-fluid" />
                                                            </div>
                                                            <div>
                                                                <h6 title="<?php echo e($emp_info->employee->resortAdmin->id); ?>"><?php echo e(@$emp_info->employee->resortAdmin->full_name); ?> (<?php echo e($emp_info->employee->position->position_title); ?> - <?php echo e($emp_info->employee->department->name); ?>(<?php echo e($emp_info->employee->department->code); ?>))</h6>
                                                                <p><?php echo e($emp_info->title); ?></p>
                                                            </div>
                                                            <div>
                                                                <?php if($emp_info->status == 'Pending'): ?>
                                                                    <a href="<?php echo e(route('people.info-update.show',$emp_info->id)); ?>"  data-bs-toggle="modal" data-bs-target="#reqApproval-modal" class="a-linkTheme open-ajax-modal">Update</a>
                                                                    <a href="#" class="a-linkDanger"  data-bs-toggle="modal" data-id="<?php echo e($emp_info->id); ?>" data-bs-target="#reqReject-modal" >Reject</a>
                                                                <?php else: ?>
                                                                    <a href="#" class="<?php if($emp_info->status == 'Approved'): ?> a-linkTheme <?php else: ?> a-linkDanger <?php endif; ?>" ><?php echo e($emp_info->status); ?></a>
                                                                <?php endif; ?>
                                                            </div>
                                                    </div>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                            </div>
                                        </div>
                                        <div class="col-sm-4">
                                            <div class="leaveUser-bgBlock talentAcqPronPeo-block">
                                               <div class="d-flex">
                                                    <h6>Total Employees On Probation</h6>
                                                    <strong><?php echo e($probationalEmployees ?? 0); ?></strong>
                                                </div>
                                                <div class="w-100 text-center">
                                                    <div class="row  g-xxl-4 g-md-2 g-2 ">
                                                        <div class="col">
                                                            <p class="fw-500">Active</p>
                                                            <h5><b><?php echo e($activeProbationCount ?? 0); ?></b></h5>
                                                        </div>
                                                        <div class="col">
                                                            <p class="fw-500">Failed</p>
                                                            <h5><b><?php echo e($failedProbationCount ?? 0); ?></b></h5>
                                                        </div>
                                                        <div class="col">
                                                            <p class="fw-500">Completed</p>
                                                            <h5><b><?php echo e($completedProbationCount ?? 0); ?></b></h5>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="leaveUser-bgBlock talentAcqPromPeo-block">
                                                <div class="card-title w-100">
                                                    <h6>Promotion</h6>
                                                </div>
                                                <div class="col-sm-6">
                                                    <div class="leaveUser-bgBlock">
                                                        <h6>Total Promotions</h6>
                                                        <strong><?php echo e($total_promotions ?? 0); ?></strong>
                                                    </div>
                                                </div>
                                                <div class="col-sm-6">
                                                    <div class="leaveUser-bgBlock">
                                                        <h6>Avg. Salary Increase</h6>
                                                        <strong><?php echo e(round($average_salary_increase,2)); ?>%</strong>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="leaveUser-bgBlock talentAcqPronPeo-block">
                                                <div class="d-flex border-0 pb-0    mb-0">
                                                    <h6>Total Exits Initiated *</h6>
                                                    <strong><?php echo e($totalExitInitiated); ?></strong>
                                                </div>
                                            </div>

                                        </div>
                                        <div class="col-sm-6">
                                            <div class="bg-themeGrayLight talentAcqAppPeo-block h-100">
                                                <div class="card-title">
                                                    <h3>Approvals *</h3>
                                                </div>
                                                <div class="leaveUser-bgBlock">
                                                    <h6>Total Pending Approvals</h6>
                                                    <strong>50</strong>
                                                </div>
                                                <div class="leaveUser-bgBlock">
                                                    <h6>Approved</h6>
                                                    <strong>25</strong>
                                                </div>
                                                <div class="leaveUser-bgBlock">
                                                    <h6>Held</h6>
                                                    <strong>15</strong>
                                                </div>
                                                <div class="leaveUser-bgBlock">
                                                    <h6>Rejected</h6>
                                                    <strong>10</strong>
                                                </div>
                                                <div class="approvalsPeopleEmp-block">
                                                    <p>Oldest Pending Request</p>
                                                    <p><i>2 Days Ago</i></p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="bg-themeGrayLight talentAcqResPeo-block h-100">
                                                <div class="card-title">
                                                    <h3>Resignation</h3>
                                                </div>
                                                <div class="leaveUser-bgBlock">
                                                    <h6>Total Resignations</h6>
                                                    <strong><?php echo e($total_resignations); ?></strong>
                                                </div>
                                                <div class="leaveUser-bgBlock">
                                                    <h6>Pending Clearance</h6>
                                                    <strong><?php echo e($pending_resignation); ?></strong>
                                                </div>
                                                <div class="leaveUser-bgBlock">
                                                    <h6>Withdrw Resignation</h6>
                                                    <strong><?php echo e($withdraw_resignation); ?></strong>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="talentAcqLiabilTrackPeo-block">
                                                <div class="card-title">
                                                    <div class="row g-md-2 g-1 align-items-center">
                                                        <div class="col">
                                                            <h3>Liability Tracker</h3>
                                                        </div>
                                                        <div class="col-auto">
                                                            <a href="#" class="a-link">View All</a>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="leaveUser-bgBlock mb-2">
                                                    <h6>Total Estimated Liability</h6>
                                                    <div>
                                                        <strong>$100,000</strong>
                                                        <span>(2026)</span>
                                                    </div>
                                                </div>
                                                <div class="row g-md-4 g-2 align-items-center">
                                                    <div class="col-sm-6">
                                                        <div class="bg-themeGrayLight">
                                                            <h6 class="fw-600 mb-2">Monthly Deduction Trend</h6>
                                                            <div class="table-responsive">
                                                                <table class="table-lableNew  w-100">
                                                                    <tbody>
                                                                        <tr>
                                                                            <td>January</td>
                                                                            <th>$1,000</th>
                                                                        </tr>
                                                                        <tr>
                                                                            <td>February</td>
                                                                            <th>$2,000</th>
                                                                        </tr>
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-sm-6">
                                                        <h6 class="fw-600 mb-2">Actual Payments Made</h6>
                                                        <div class="d-flex align-items-center  mb-lg-3 mb-2">
                                                            <div
                                                                class="progress progress-custom progress-themeskyblue flex-grow-1 me-2">
                                                                <div class="progress-bar" role="progressbar"
                                                                    style="width: 65%" aria-valuenow="65"
                                                                    aria-valuemin="0" aria-valuemax="100"></div>
                                                            </div>
                                                            <span>$20,000</span>
                                                        </div>
                                                        <h6 class="fw-600 mb-2">Manual Adjustments</h6>
                                                        <p>3 Adjustments, Total: $1,500</p>
                                                    </div>
                                                    <div class="col-12">
                                                        <h6 class="fw-600 mb-md-2 mb-1">Estimation vs. Actual Comparison
                                                        </h6>
                                                        <div class="table-responsive">
                                                            <table
                                                                class="table-lableNew table-liabilityTrackPeopleEmp w-100">
                                                                <thead>
                                                                    <tr>
                                                                        <th>Cost Category</th>
                                                                        <th>Estimated Cost</th>
                                                                        <th>Actual Cost</th>
                                                                        <th>Remaining Liability</th>
                                                                        <th>Remaining Liability</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    <tr>
                                                                        <td>Overtime</td>
                                                                        <td>$5,000</td>
                                                                        <td>$4,500</td>
                                                                        <td>$500</td>
                                                                        <td><span class="text-themeSuccess">$500</span>
                                                                        </td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td>Loans</td>
                                                                        <td>$10,000</td>
                                                                        <td>$8,000</td>
                                                                        <td>$2,000</td>
                                                                        <td><span class="text-themeDanger">-$600</span>
                                                                        </td>
                                                                    </tr>
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
                    </div>
                </div>
            </div>

        </div>
    </div>
    

<!-- Modal HTML -->
<div id="rejectionModal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reason for Rejection</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <textarea id="rejectionReason" class="form-control" rows="3" placeholder="Enter a reason (optional)"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" id="confirmRejectBtn" class="btn btn-danger">Reject</button>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('import-css'); ?>
<style>
    .monthlyCheck-block .img-circle img {
    object-fit: cover;
}
</style>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('import-scripts'); ?>
<script type="text/javascript">
const timeZone = Intl.DateTimeFormat().resolvedOptions().timeZone;

  var isDateSelected = false;
    $(".table-icon").click(function () {
        $(this).parents('tr').toggleClass("in");
    });

    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });

    // full-calendar
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
            eventLimit: 0, // Allow "more" link when too many events
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

                            // Adjust based on response format
                            if (errors && errors.errors) {
                                $.each(errors.errors, function(key, error) {
                                    console.log(error);
                                    errs += error + '<br>';
                                });
                            } else {
                                errs = "An unexpected error occurred.";
                            }

                            // Display errors
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

        $(document).on("change", "#ResortPosition", function () {
            let PositionId = $(this).val();
            $.ajax({
                url: "<?php echo e(route('resort.ta.GePositionWiseTopAppliants')); ?>",
                type: "POST",
                data: {
                    PositionId: PositionId,
                    _token: "<?php echo e(csrf_token()); ?>"
                },
                success: function (response) {
                    let string1 = '';

                    // Check if response contains the applicant trends
                    if (response && response.applicantTrends) {
                        // Loop through the trends and construct rows
                        $.each(response.applicantTrends, function (i, v) {
                            string1 += `
                                <tr>
                                    <td><img src="${v.flag_url}" alt="flag" class="flag">${v.country}</td>
                                    <td>${v.latest_count}</td>
                                    <td><img src="${v.trend}" alt="icon"></td>
                                </tr>`;
                        });


                        $("#topCountriesWiseCount").html(string1);            }
                },
                error: function (xhr, status, error) {
                    console.error("AJAX Error:", error);
                    alert("An error occurred while fetching data.");
                }
            });
        });


        var cty = document.getElementById('myStackedBarChart').getContext('2d');
        // Function to fetch chart data dynamically
        function fetchChartData(year) {
            $.ajax({
                url: "<?php echo e(route('payroll.getExpenses')); ?>", // Adjust route accordingly
                method: "GET",
                data: { year: year },
                success: function (response) {
                    if (response.success) {
                        updateChart(response.labels, response.data);
                    }
                },
                error: function (xhr, error, code) {
                    console.error("Error fetching chart data:", error);
                }
            });
        }

        // Function to update the chart dynamically
        function updateChart(labels, datasetValues) {
            myStackedBarChart.data.labels = labels;
            myStackedBarChart.data.datasets[0].data = datasetValues.payrollCost;
            myStackedBarChart.data.datasets[1].data = datasetValues.otCost;
            myStackedBarChart.data.datasets[2].data = datasetValues.serviceCharge;
            myStackedBarChart.update(); // Update the chart
        }

        // Initialize the chart
        var myStackedBarChart = new Chart(cty, {
            type: 'bar',
            data: {
                labels: [], // Initially empty, will be updated via AJAX
                datasets: [
                    {
                        label: 'Payroll Cost',
                        data: [],
                        backgroundColor: '#014653',
                        borderColor: '#fff',
                        borderWidth: 2,
                        borderRadius: 10,
                    },
                    {
                        label: 'OT Cost',
                        data: [],
                        backgroundColor: '#2EACB3',
                        borderColor: '#fff',
                        borderWidth: 2,
                        borderRadius: 10,
                    },
                    {
                        label: 'Service Charge',
                        data: [],
                        backgroundColor: '#EFB408',
                        borderColor: '#fff',
                        borderWidth: 2,
                        borderRadius: 10,
                    },
                ]
            },
            options: {
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        enabled: true,
                        mode: 'index',
                        intersect: false,
                        callbacks: {
                            label: function (tooltipItem) {
                                return tooltipItem.dataset.label;
                            },
                            afterLabel: function (tooltipItem) {
                                return '$' + tooltipItem.raw;
                            }
                        },
                        displayColors: false
                    }
                },
                scales: {
                    x: { stacked: true, grid: { display: false } },
                    y: { stacked: true, beginAtZero: true, grid: { display: false } }
                }
            }
        });

        // Fetch initial chart data
        let initialYear = $("#yearFilter").val();
        fetchChartData(initialYear);

        // Change event for the year filter
        $("#yearFilter").change(function () {
            let selectedYear = $(this).val();
            fetchChartData(selectedYear);
        });

</script>

<script type="text/javascript">
    $(document).ready(function() {
        $('#leave-request-table tbody').empty();

        if ($.fn.DataTable.isDataTable('#leave-request-table'))
        {
            $('#leave-request-table').DataTable().destroy();
        }

        var table = $('#leave-request-table').DataTable({
            searching: false,
            bLengthChange: false,
            bFilter: true,
            bInfo: true,
            bAutoWidth: false,
            scrollX: true,
            iDisplayLength: 10,
            processing: true,
            serverSide: true,
            ajax: function(data, callback, settings) {
                // Get the department filter value
                var departmentId = $('#department-filter').val();

                $.ajax({
                    url: "<?php echo e(route('leave-requests.get')); ?>",
                    method: "GET",
                    data: {
                        department_id: departmentId,
                        start: settings.start, // For pagination
                        length: settings.length, // For pagination
                    },
                    success: function(response) {
                        callback({
                            draw: settings.draw,
                            recordsTotal: response.recordsTotal, // Total records
                            recordsFiltered: response.recordsFiltered, // Filtered records
                            data: response.data // Data for the current page
                        });
                    }
                });
            },
            columns: [
                {   
                    data: 'employee_id' },
                {   
                    data: 'full_name', render: function(data, type, row) {
                    return '<div class="tableUser-block"><div class="img-circle"><img src="'+row.profile_picture+'" alt="user"></div><span class="userApplicants-btn">'+ row.first_name + ' ' + row.last_name + '</span></div>';
                }},
                {   
                    data: 'total_days' },
                {   
                    data: 'leave_status',
                    render: function(data, type, row) {
                        let statusClass = 'badge-secondary'; // Default class

                        // Check for specific keywords in the status text and assign the appropriate class
                        if (row.status_text.includes('Approved')) {
                            statusClass = 'badge-themeSuccess'; // Green for approved
                        } else if (row.status_text.includes('Rejected')) {
                            statusClass = 'badge-themeDanger'; // Red for rejected
                        } else if (row.status_text.includes('Pending')) {
                            statusClass = 'badge-themeWarning'; // Yellow for pending
                        }

                        // Render the badge with the dynamic class and status text
                        return `<span class="badge ${statusClass}">${row.status_text}</span>`;
                    }
                },
                {
                    data: 'action',
                    render: function(data, type, row) {

                        return `
                            <a title="Leave Details" href="${row.routes}" class="eye-btn mx-1">
                                <i class="fa-regular fa-eye"></i>
                            </a>
                            <a href="javascript:void(0);" class="correct-btn mx-1 approve-btn" data-leave-id="${row.id}"">
                                    <i class="fa-solid fa-check"></i>
                            </a>
                            <a href="javascript:void(0);" class="close-btn mx-1 reject-btn" data-leave-id="${row.id}"">
                                <i class="fa-solid fa-xmark"></i>
                            </a>
                        `;
                    }
                }
            ],
        });

        // Trigger table reload when department filter changes
        $('#department-filter').on('change', function() {
            table.ajax.reload(); 
        });

        let currentLeaveId = null; 

        $('#leave-request-table').on('click', '.approve-btn', function () {
            const leaveId = $(this).data('leave-id');

            // Perform the approval action
            handleLeaveAction(leaveId, 'Approved', '');
        });

        $('#leave-request-table').on('click', '.reject-btn', function () {
            currentLeaveId = $(this).data('leave-id');

            // Show the rejection modal
            $('#rejectionModal').modal('show');
        });

        $('#confirmRejectBtn').on('click', function () {
            const reason = $('#rejectionReason').val(); // Get the reason (optional)

            // Perform the rejection action
            handleLeaveAction(currentLeaveId, 'Rejected', reason);

            // Hide the modal
            $('#rejectionModal').modal('hide');
            $('#rejectionReason').val(''); // Clear the input for the next use
        });

        function handleLeaveAction(leaveId, action, reason) {
            $.ajax({
                url: "<?php echo e(route('leave.handleAction')); ?>",
                method: 'POST',
                data: {
                    leave_id: leaveId,
                    action: action,
                    reason: reason,
                    _token: $('meta[name="csrf-token"]').attr('content'),
                },
                success: function (response) {
                    // console.log(response);
                    if (response.status == "success") {
                        toastr.success(response.message, "Success", {
                            positionClass: 'toast-bottom-right'
                        });

                        window.setTimeout(function () {
                            window.location.reload();
                        }, 2000);
                    } else {
                        toastr.error(response.message, "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                },
                error: function (xhr) {
                    if (xhr.status === 403) {
                        let response = xhr.responseJSON;
                        if (response.status == "error") {
                            toastr.error(response.message, "Error", {
                                positionClass: 'toast-bottom-right'
                            });
                        }
                    } else {
                        toastr.error("An unexpected error occurred. Please try again.", "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                }
            });
        }    });
     
     const cty = document.getElementById('myBarChart').getContext('2d');
    const myBarChart = new Chart(cty, {
        type: 'bar', // Type of chart
        data: {
            labels: ['2021', '2022', '2023', '2024'], // X-axis labels
            datasets: [
                {
                    label: 'Budgeted', // Label for the first dataset
                    data: [1800, 2300, 2400, 1800], // Data for the first dataset
                    backgroundColor: '#014653',
                    borderRadius: 3, // Set the border radius for bars
                    barThickness: 14 // Set the width of the bars
                },
                {
                    label: 'Actual', // Label for the second dataset
                    data: [2000, 2200, 2000, 2400], // Data for the second dataset
                    backgroundColor: '#2EACB3',
                    borderRadius: 3, // Set the border radius for bars
                    barThickness: 14 // Set the width of the bars
                }
            ]
        },
        options: {
            plugins: {
                legend: {
                    display: false
                },
                layout: {
                    padding: {
                        top: 0,
                        bottom: 0,
                        left: 0,
                        right: 0
                    }
                },
                tooltip: {
                    enabled: true, // Enable tooltips
                    callbacks: {
                        label: function (tooltipItem) {
                            // const datasetLabel = tooltipItem.dataset.label || '';
                            const value = tooltipItem.raw.toLocaleString(); // Format the value with commas
                            return ` $${value}`; // Custom tooltip format
                        }
                    }
                }
            },
            scales: {
                x: {
                    beginAtZero: true, // Start x-axis at zero
                    grid: {
                        display: false // Hide grid lines on the x-axis
                    },
                    border: {
                        display: true // Show the x-axis border
                    }
                },
                y: {
                    beginAtZero: false, // Do not start y-axis at zero
                    min: 100, // Set the minimum value for y-axis
                    grid: {
                        display: false // Hide grid lines on the y-axis
                    },
                    border: {
                        display: true // Show the y-axis border
                    },
                    ticks: {
                        callback: function (value) {
                            return `$${value.toLocaleString()}`; // Format y-axis labels as currency
                        }
                    }
                }
            }
        }
    });

    $(document).ready(function(){

         const ctx = document.getElementById('myAttendance').getContext('2d');
        const currentYear = new Date().getFullYear();
        const selectedYear = currentYear; // Default selected year

        function generateLabels(year) {
            const labels = [];
            const firstMonth = 0; // January (0-indexed in JavaScript)
            const lastMonth = 11; // December (0-indexed in JavaScript)

            for (let i = firstMonth; i <= lastMonth; i++) {
                const month = new Date(year, i);
                labels.push(month.toLocaleString('default', { month: 'short', year: 'numeric' }));
            }
            return labels;
        }

        const myAttendance = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: generateLabels(selectedYear),
                datasets: []
            },
            options: {
                plugins: {
                    legend: {
                        display: false
                    },
                    layout: {
                        padding: {
                            top: 0,
                            bottom: 0,
                            left: 0,
                            right: 0
                        }
                    },
                    tooltip: {
                        enabled: true, // Enable tooltips
                        callbacks: {
                            label: function (tooltipItem) {
                                const value = tooltipItem.raw.toLocaleString();
                                return `${value}%`;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true, // Start x-axis at zero
                        grid: {
                            display: false // Hide grid lines on the x-axis
                        },
                        border: {
                            display: true // Show the x-axis border
                        }
                    },
                    y: {
                        beginAtZero: true, // Do not start y-axis at zero
                        grid: {
                            display: false // Hide grid lines on the y-axis
                        },
                        ticks: {
                            stepSize: 20,
                        },
                        border: {
                            display: true // Show the y-axis border
                        },
                    }
                }
            }

        });
        function updateAttendanceChart(year) {
            $.ajax({
                url: "<?php echo e(route('resort.recruitement.getAttandanceData')); ?>",
                type: "POST",
                data: {
                    year: year,
                    "_token": "<?php echo e(csrf_token()); ?>"
                },
                success: function (response) {
                    if (response.datasets && Array.isArray(response.datasets)) {
                        myAttendance.data.labels = generateLabels(year);
                        myAttendance.data.datasets = response.datasets;
                        myAttendance.update();
                    } else {
                        console.error("Invalid datasets format:", response.datasets);
                    }
                },
                error: function (xhr) {
                    console.error("Failed to fetch chart data", xhr);
                }
            });
        }

        $(document).on('change', '.YearWiseAttandance', function () {
            const selectedYear = $(this).val();
            updateAttendanceChart(selectedYear);
        });

        // Initial chart load
        updateAttendanceChart(selectedYear);
        loadUpcomingMeetings();
    });
    
  
     function loadUpcomingMeetings() {
            $.ajax({
                url: '<?php echo e(route("incident.getUpcomingMeetings")); ?>',
                method: 'GET',
                success: function (meetings) {
                    let container = $('#upcoming-meetings');
                    container.empty();

                    if (meetings.length === 0) {
                        container.append('<div class="text-muted px-3 py-2">No upcoming meetings.</div>');
                        return;
                    }

                    meetings.forEach(meeting => {
                        container.append(`
                            <div class="leaveUser-block">
                                <div>
                                    <div class="d-flex justify-content-between">
                                        <h6>${meeting.title}</h6>
                                        <span class="badge badge-themeNew1 border-0">${meeting.day_label}, ${meeting.scheduled_time}</span>
                                    </div>
                                    <p>${meeting.description}</p>
                                    <div>
                                        <a href="${meetingDetailBaseUrl.replace('MEETING_ID', btoa(meeting.id))}" class="a-linkTheme">View Details</a>
                                    </div>
                                </div>
                            </div>
                        `);
                    });
                }
            });
        }

</script>

<script type="module">
        // Get the canvas and its data attributes
        var canvas = document.getElementById('myDoughnutChart');
        var ctx = canvas.getContext('2d');
        var maleCount = parseInt(canvas.getAttribute('data-male')) || 0;
        var femaleCount = parseInt(canvas.getAttribute('data-female')) || 0;

        // Custom plugin for inside labels
        const doughnutLabelsInside = {
            id: 'doughnutLabelsInside',
            afterDraw: function (chart) {
                var ctx = chart.ctx;
                chart.data.datasets.forEach(function (dataset, i) {
                    var meta = chart.getDatasetMeta(i);
                    if (!meta.hidden) {
                        meta.data.forEach(function (element, index) {
                            var dataValue = dataset.data[index];
                            var total = dataset.data.reduce((acc, val) => acc + val, 0);
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

        // Chart config
        var myDoughnutChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Male', 'Female'],
                datasets: [{
                    data: [maleCount, femaleCount],
                    backgroundColor: ['#2EACB3', '#014653'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    doughnutLabelsInside: true,
                    legend: {
                        display: false
                    }
                },
                layout: {
                    padding: {
                        top: 10,
                        bottom: 10
                    }
                }
            },
            plugins: [doughnutLabelsInside]
        });


        // Function to update the chart with new data for Payroll Service Charges
        $(document).ready(function () {
            // Initialize the service charge chart
            GetServiceChargeChart();
        });

        let myDoughnutChartService = null; // Reset the chart variable
        const ctz = document.getElementById('myDoughnutChartService').getContext('2d');
        const doughnutLabelsInsideN = {
            id: 'doughnutLabelsInsideN',
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
                            ctx.font = 'normal 18px Poppins';
                            ctx.textAlign = 'center';
                            ctx.textBaseline = 'middle';
                            ctx.fillText(percentage, position.x, position.y);
                        });
                    }
                });
            }
        };

        const centerText = {
            id: 'centerText',
            afterDraw: function (chart) {
                const width = chart.width;
                const height = chart.height;
                const ctx = chart.ctx;

                ctx.restore();

                // Calculate total dynamically from the chart data
                const total = chart.data.datasets[0].data.reduce((a, b) => a + b, 0);

                // Format the total as a currency value
                const formattedTotal = '$' + total.toLocaleString();

                // Text configuration
                ctx.textBaseline = 'middle';
                ctx.textAlign = 'center';

                // Total number
                ctx.font = '500 22px Poppins';
                ctx.fillStyle = '#222222';
                // ctx.fillText('$' + total, width / 2, height / 2 - 15);
                ctx.fillText(formattedTotal, width / 2, height / 2 - 15);

                // "Total" label
                ctx.font = '500 13px Poppins';
                ctx.fillStyle = '#222222';
                ctx.fillText('Avg', width / 2, height / 2 + 15);

                ctx.save();
            }
        };
    function GetServiceChargeChart() {
        
        $.ajax({
            url: "<?php echo e(route('chart.service-charges')); ?>", // Replace with your actual route
            type: "POST",
            data: {
                "_token": "<?php echo e(csrf_token()); ?>",
                "YearWiseServichCharges": $(".YearWiseServichCharges").val()
            },
            success: function (response) {
                console.log(response);
                const data = response.data;
                const total = response.total;
                const labels = data.map(item => item.label);
                const serviceCharges = data.map(item => item.service_charge);
                const serviceChargespercentage = data.map(item => item.percentage);
                const colors = ['#014653', '#53CAFF', '#EFB408', '#50B9BF', '#333333', '#8DC9C9'];

                // Check if the chart exists and destroy it
                if (myDoughnutChartService !== null && typeof myDoughnutChartService.destroy === 'function') {
                    myDoughnutChartService.destroy();
                }
                // Update the chart
                myDoughnutChartService = new Chart(document.getElementById('myDoughnutChartService'), {
                    type: 'doughnut',
                    data: {
                        labels: labels,
                        datasets: [{
                            data: serviceChargespercentage,
                            backgroundColor: colors,
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
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
                        }
                    },
                    plugins: [doughnutLabelsInsideN, centerText] // Attach the plugin to this chart only
                });
                // Update the side labels
                const labelContainer = document.getElementById('myDoughnutChartServiceLabel');
                let labelsHTML = '';
                data.forEach((item, index) => {
                    labelsHTML += `
                        <div class="col-6">
                            <div class="doughnut-label">
                                <span style="background-color: ${colors[index]}"></span>${item.label} <br>$${item.service_charge}
                            </div>
                        </div>
                    `;
                });
                // Add total row
                labelsHTML += `
                    <div class="fw-500">Total: $${total}</div>
                `;

                // Insert into the DOM
                labelContainer.innerHTML = labelsHTML;
            },
            error: function (xhr) {
                console.error("Failed to fetch chart data", xhr);
            }
        });
    }

    // Trigger data load on dropdown change
    $(document).on("change", ".YearWiseServichCharges", function () {
        GetServiceChargeChart();
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
<?php $__env->stopSection(); ?>


<?php echo $__env->make('resorts.layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /workspaces/HRVMS-Wisdom-AI/resources/views/resorts/master-dashboard/hrdashboard.blade.php ENDPATH**/ ?>