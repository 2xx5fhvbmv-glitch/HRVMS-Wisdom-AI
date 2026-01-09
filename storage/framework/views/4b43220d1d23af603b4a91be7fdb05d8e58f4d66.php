
<?php $__env->startSection('page_tab_title' ,"Resort Admin Dashboard"); ?>

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
                        <span>WORKFORCE PLANNING</span>
                        <h1>HR Dashboard</h1>
                    </div>
                </div>
                <div class="col-auto">
                    <div class="d-flex justify-content-end">
                        <a href="#sendRequest-modal" data-bs-toggle="modal" class=" btn btn-sm btn-theme">Request Manning</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-xl-3 col-md-6 order-xl-1 order-2">
                <div class="position-xl-sticky sidebar ">

                    <div class="card budget-e-box mb-30 <?php if(App\Helpers\Common::checkRouteWisePermission('resort.budget.viewbudget', config('settings.resort_permissions.view')) == false): ?>d-none <?php endif; ?>">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4>Total Budgeted Employees</h4>
                            <strong><?php echo e($manning_response->TotalBudgtedemp ?? '0'); ?></strong>
                        </div>
                        <div>
                            <div class="grey-box">
                                <a href="<?php echo e(route('resort.workforceplan.filledpositions')); ?>" class="d-flex justify-content-between text-dark">
                                    <p class="mb-0 fw-500">Filled Positions</p>
                                    <span class="fw-bold"><?php echo e($manning_response->total_filled_positions_count ?? '0'); ?></span>
                                </a>
                                <div class="progress">
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
                            <div class="grey-box">
                                <a href="#" class="d-flex justify-content-between text-dark">
                                    <p class="mb-0 fw-500">Vacant</p>
                                    <span class="fw-bold"><?php echo e($manning_response->total_vacant_count ?? '0'); ?></span>
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="card  mb-30">
                        <div class="card-title d-flex justify-content-between">
                            <h3>Employee Type</h3>
                        </div>
                        <div class="mb-3 text-center">
                            <canvas id="myDoughnutChart"  width="206" height="206" class="mb-3"></canvas>
                            <div class="row g-2 justify-content-center">
                                <div class="col-auto">
                                    <div class="doughnut-label">
                                        <span class="bg-theme"></span>Local Maldivian
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <div class="doughnut-label">
                                        <span class="bg-themeLightBlue"></span>Expatriate Employees
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Male Progress Bar -->
                        <div class="d-flex align-items-center justify-content-between progress-employee-type">
                            <div class="progress w-100">
                                <div class="progress-bar" role="progressbar"
                                    style="width: <?php echo e($male_percentage); ?>%"
                                    aria-valuenow="<?php echo e($male_percentage); ?>"
                                    aria-valuemin="0"
                                    aria-valuemax="100">
                                    <?php echo e($male_percentage); ?>%
                                </div>
                            </div>
                            <label>Male</label>
                        </div>


                        <!-- Female Progress Bar -->
                        <div class="d-flex align-items-center justify-content-between progress-employee-type">
                            <div class="progress w-100">
                                <div class="progress-bar" role="progressbar"
                                    style="width: <?php echo e($female_percentage); ?>%"
                                    aria-valuenow="<?php echo e($female_percentage); ?>"
                                    aria-valuemin="0"
                                    aria-valuemax="100">
                                    <?php echo e($female_percentage); ?>%
                                </div>
                            </div>
                            <label>Female</label>
                        </div>
                    </div>

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
                                        <div>
                                            <div class="d-flex justify-content-center date-slider">
                                                <!-- <a href="#"><i class="fa-solid fa-angle-left"></i></a> -->
                                                <a href="#" data-bs-toggle="tooltip" data-bs-placement="right" title="Tooltip on right"><?php echo e(date('d M Y',strtotime($oc->occupancydate))); ?></a>
                                                <!-- <a href="#"><i class="fa-solid fa-angle-right"></i></a> -->
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
                                    <div>
                                        <div class="d-flex justify-content-center date-slider">
                                            <!-- <a href="#"><i class="fa-solid fa-angle-left"></i></a> -->
                                            <a href="#" data-bs-toggle="tooltip" data-bs-placement="right" title="Tooltip on right"><?php echo e(date('d M Y')); ?></a>
                                            <!-- <a href="#"><i class="fa-solid fa-angle-right"></i></a> -->
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

                    <div class="card  mb-30">
                        <div class="card-title d-flex justify-content-between">
                            <h3>Compliance Tracking</h3>
                        </div>
                        <div>
                            <div class="d-flex justify-content-between mb-3 border-bottom pb-2">
                                <p class="mb-0">
                                    Number of Local/Xpat:
                                </p>
                                <span class="d-inline-block w-25 text-end"><?php echo e($localEmployees); ?>/<?php echo e($expatEmployees); ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-3 border-bottom pb-2">
                                <p class="mb-0">
                                    Employees Under Minimum Wage:
                                </p>
                                <span class="d-inline-block w-25 text-end text-danger"><?php echo e($employee_under_min_wage); ?></span>
                            </div>
                            <!-- <div class="d-flex justify-content-between mb-3  pb-2">
                                <p class="mb-0">
                                    Exceeding Budgeted Amounts:
                                </p>
                                <span class="d-inline-block w-25 text-end text-danger">$0</span>
                            </div> -->
                        </div>

                    </div>

                </div>
            </div>
            <div class="col-xl-6 order-xl-2 order-first">
                <div class="<?php if(App\Helpers\Common::checkRouteWisePermission('resort.budget.manning', config('settings.resort_permissions.view')) == false): ?> d-none <?php endif; ?>">
                    <div class="row justify-content-between mb-30 g-3 ">
                        <div class="col-md-4">
                            <div class="card dashboard-boxcard">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <p class="mb-0  fw-500">Divisions</p>
                                        <strong><?php echo e($resort_divisions_count); ?></strong>
                                    </div>
                                    <a href="<?php echo e(route('resort.manning.index')); ?>">
                                        <img src="<?php echo e(URL::asset('resorts_assets/images/arrow-right-circle.svg')); ?>" alt="" class="img-fluid" />
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card dashboard-boxcard">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <p class="mb-0  fw-500">Departments</p>
                                        <strong><?php echo e($resort_departments_count); ?></strong>
                                    </div>
                                    <a href="<?php echo e(route('resort.manning.index')); ?>">
                                        <img src="<?php echo e(URL::asset('resorts_assets/images/arrow-right-circle.svg')); ?>" alt="" class="img-fluid" />
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card dashboard-boxcard">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <p class="mb-0  fw-500">Positions</p>
                                        <strong><?php echo e($resort_positions_count); ?></strong>
                                    </div>
                                    <a href="<?php echo e(route('resort.manning.index')); ?>">
                                        <img src="<?php echo e(URL::asset('resorts_assets/images/arrow-right-circle.svg')); ?>" alt="" class="img-fluid" />
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="<?php if(App\Helpers\Common::checkRouteWisePermission('resort.budget.consolidatebudget', config('settings.resort_permissions.view')) == false): ?> d-none <?php endif; ?>">
                    <div class="card">
                        <div class="card-title">
                            <div class="row justify-content-between align-items-center g-3 ">
                                <div class="col-auto">
                                    <h3>Workforce Planning</h3>
                                </div>
                                <div class="col-auto">

                                    <div class="d-flex align-items-center justify-content-sm-end">
                                        <div class="form-group">
                                            <select class="form-select ManningBudgetMonthWise" aria-label="Default select example ">
                                                <!-- <?php for($i = 0; $i <= 11; $i++): ?>
                                                    <?php
                                                        $currentYear = date('Y');
                                                        $currentMonth = date('m');
                                                        $targetMonth = $currentMonth + $i;
                                                        if($targetMonth > 12) {
                                                            break;
                                                        }
                                                        $yearMonthvalue = date("m-Y", mktime(0, 0, 0, $currentMonth + $i, 1, $currentYear));
                                                        $yearMonth = date("M-Y", mktime(0, 0, 0, $currentMonth + $i, 1, $currentYear));
                                                    ?>
                                                    <option value="<?php echo e($yearMonthvalue); ?>" <?php if($yearMonthvalue == date("m-Y")): ?> selected <?php endif; ?>><?php echo e($yearMonth); ?></option>
                                                <?php endfor; ?> -->

                                                <?php for($i = 1; $i <= 12; $i++): ?>
                                                    <?php
                                                        $currentYear = date('Y');
                                                        $yearMonthValue = str_pad($i, 2, '0', STR_PAD_LEFT) . '-' . $currentYear; // "01-2025"
                                                        $yearMonth = date("M-Y", mktime(0, 0, 0, $i, 1, $currentYear));           // "Jan-2025"
                                                    ?>
                                                    <option value="<?php echo e($yearMonthValue); ?>" <?php if($yearMonthValue == date("m-Y")): ?> selected <?php endif; ?>>
                                                        <?php echo e($yearMonth); ?>

                                                    </option>
                                                <?php endfor; ?>
                                            </select>
                                        </div>

                                        <div class="ms-3">
                                            <a href="#" class="btn btn-theme btn-sm" id="downloadManningBudget">
                                                <i class="fa-solid fa-download me-1"></i> Download
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div id="pdf-wrapper">
                            <!-- HEADER (only visible in PDF) -->
                            <div class="pdf-header">
                                <table style="width: 100%; border-collapse: collapse;">
                                    <tr>
                                        <td style="padding: 15px;" rowspan="2">
                                            <img src="<?php echo e(Common::GetResortLogo($ResortData->id)); ?>" alt="Logo" style="width: 150px;">
                                        </td>
                                        <td style="text-align: right; padding: 15px;">
                                            <div style="font-size: 20px; font-weight: 400; color: #fff;">
                                                <?php echo e($ResortData->resort_name); ?>

                                            </div>
                                            <div style="font-size: 14px; color: #fff; padding-top: 4px;">
                                                <?php echo e($ResortData->address1); ?>, <?php echo e($ResortData->address2); ?><br>
                                                <?php echo e($ResortData->state); ?> - <?php echo e($ResortData->city); ?>, <?php echo e($ResortData->zip); ?><br>
                                                <?php echo e($ResortData->country); ?>

                                            </div>
                                        </td>
                                    </tr>
                                </table>
                            </div>

                            <!-- <div class="position-relative ">
                                <div id="loader">
                                <img src="<?php echo e(URL::asset('resorts_assets/images/ajax-loader.gif')); ?>" alt="loader" />
                                </div> -->
                            
                                <div class="accordion budget-accordion appendManningBudgetMontly" id="accordionExample">
                                    <?php if($resort_divisions): ?>
                                        <?php $__currentLoopData = $resort_divisions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <div class="accordion-item">
                                            <h2 class="accordion-header" id="heading<?php echo e($key); ?>">
                                                <button class="accordion-button <?php echo e($key == 0 ? '' : 'collapsed'); ?>"
                                                type="button" data-bs-toggle="collapse"
                                                    data-bs-target="#collapse<?php echo e($key); ?>" aria-expanded="<?php echo e($key == 0 ? 'true' : 'false'); ?>"
                                                    aria-controls="collapse<?php echo e($key); ?>">
                                                    <div class="d-flex align-items-center justify-content-between w-100 pe-sm-4 pe-1">
                                                        <span class="name"> <?php echo e($value->name); ?></span>
                                                        <span class="lable-budget">Budget: <img class="currency-budget-icon" src="<?php echo e($currency); ?>"> 00.00</span>
                                                    </div>
                                                </button>
                                            </h2>
                                            <div id="collapse<?php echo e($key); ?>" class="accordion-collapse collapse <?php echo e($key == 0 ? 'show' : ''); ?>"
                                                aria-labelledby="heading<?php echo e($key); ?>" data-bs-parent="#accordionExample">
                                                <div class="accordion-body">
                                                    <?php $__currentLoopData = $resort_departments->where('division_id', $value->id); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $department): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                        <?php
                                                            $array = [];
                                                            if(!empty($department->monthWiseBudget)) {
                                                                foreach ($department->monthWiseBudget as $Key => $item) {
                                                                    $array['dept_id'] = $item['dept_id'];
                                                                    $array['position_monthly_data_id'][] = $item['id'];
                                                                }
                                                            }
                                                        ?>
                                                        <div class="accordion-innerbox bg-white d-flex justify-content-between align-items-center">
                                                            <form id="departmentBudget" method="POST" action="<?php echo e(route('resort.department.wise.budget.data')); ?>">
                                                                <?php echo csrf_field(); ?>
                                                            <a href="javascript::void(0)" target="_blank">
                                                                    <p class="mb-0 fw-500 departmentBudget">
                                                                        <input type="hidden" name="data[]" value="<?php echo e(json_encode($array)); ?>">
                                                                        <?php echo e($department->name); ?>

                                                                        <span class="badge ms-sm-3 badge-warning">Review Pending</span>
                                                                    </p>
                                                                </a>
                                                                <button type="submit" class="submitBtn" style="display: none;">Submit</button>
                                                            </form>
                                                            <span class="fw-normal">Budget: <img class="currency-budget-icon" src="<?php echo e($currency); ?>"> 00.00</span>
                                                        </div>
                                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                                                </div>
                                            </div>
                                        </div>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    <?php endif; ?>
                                </div>
                            <!-- </div> -->
                            <!-- FOOTER (only visible in PDF) -->
                            <div class="pdf-footer">
                                <table style="width: 100%; border-top: 2px solid #ccc; margin-top: 30px;">
                                    <tr>
                                        <td style="text-align: left; padding: 10px;">
                                            <div style="font-size: 14px;">
                                                HRVMS - Employee Budget
                                            </div>
                                        </td>
                                        <td style="text-align: right; padding: 10px;">
                                            Page <span class="pageNumber"></span> of <span class="totalPages"></span>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 order-last">
                <div class="position-xl-sticky sidebar">
                    <div class="card budget-e-box mb-30 HrRequestViewCard" >

                            <div class="card-title d-flex justify-content-between">
                            <h3>Requests</h3>
                        </div>
                        <div class="bg-green send-m-display d-flex align-items-center justify-content-between mt-1">
                            <div class="">
                                <h5 class="fs-18 fw-500">Sent</h5>
                                <strong class="ReponseDepartmentCount"><?php echo e($ManningPendingRequestCount); ?></strong>
                            </div>
                            <img src="<?php echo e(URL::asset('resorts_assets/images/send.svg')); ?>" class="img-fluid" alt="" />
                        </div>
                        <p class="mt-4 mb-2 fw-600 PendingResponsesCount" >Pending Responses - <?php echo e($HODpendingResponse); ?></p>
                        <div class="send-reminder-box bg-grey">
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb">
                                    <?php if(isset($PendingDepartmentResoponse) && !empty($PendingDepartmentResoponse) ): ?>


                                    <?php $__currentLoopData = $PendingDepartmentResoponse; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key=> $response): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>

                                    <li class="breadcrumb-item"><a href="#"><?php echo e($response[0]); ?></a></li>

                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        <li class="breadcrumb-item">
                                            <a href="#Pending-Department"  data-bs-toggle="modal"  class="Pending-Department text-theme fw-600 text-underline">View All </a>
                                        </li>
                                    <?php else: ?>

                                    <li class="breadcrumb-item">
                                        <a href="#"
                                                class="text-theme fw-600 text-underline">No Pending Request Found </a>
                                            </li>

                                    <?php endif; ?>

                                </ol>

                            </nav>
                            <div class="d-flex justify-content-center mt-3">
                                <?php if(isset($PendingDepartmentResoponse) && !empty($PendingDepartmentResoponse) ): ?>

                                <a href="#sendReminder-modal"  data-bs-toggle="modal"  class="btn btn-theme mx-auto">Send Reminder</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="card  mb-30 <?php if(App\Helpers\Common::checkRouteWisePermission('resort.budget.consolidatebudget', config('settings.resort_permissions.view')) == false): ?> d-none <?php endif; ?>">
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
                    <div class="card  mb-30">
                        <div class="card-title d-flex justify-content-between">
                            <h3>AI Insights</h3>
                        </div>
                        <p class="chart-title">Expected Staffing Needs</p>
                        <canvas id="myLineChart" class="mb-3"></canvas>
                        <div class="row g-2 ">
                            <div class="col-auto">
                                <div class="doughnut-label">
                                    <span class="bg-theme"></span>Occupancy Rates
                                </div>
                            </div>
                            
                            <div class="col-auto">
                                <div class="doughnut-label">
                                    <span class="bg-themeNeon"></span>Hiring Data
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
    $msg = config('settings.manning_request');

?>
<div class="modal fade" id="add-occupancymodal" tabindex="-1" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered modal-small">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="staticBackdropLabel">Add Occupancy</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
            <form id="AddoccupancyForm">
                <?php echo csrf_field(); ?>
                <div class="modal-body">

                        <div class="form-group mb-20 position-relative">
                            <input type="text" class="form-control  occupancydate" name="occupancydate" placeholder="Select Date">
                        </div>

                        <div class="form-group  mb-20">
                            <input type="number" class="form-control"  min="1" name="occupancyOccupiedRooms"  id="occupancyOccupiedRooms"  placeholder="Occupied Rooms">
                        </div>
                        <div class="form-group mb-20">
                            <input type="number" class="form-control" min="1" name="occupancytotalRooms" id="occupancytotalRooms"  placeholder="Total Rooms">
                        </div>

                        <div class="form-group mb-20">
                            <input type="text" readonly class="form-control occupancyinPer"  name="occupancyinPer" placeholder="Occupancy In %">
                        </div>

                </div>
                <div class="modal-footer justify-content-end">

                    <a href="#Import-occupancymodal"  data-bs-dismiss="modal"  data-bs-toggle="modal" class="btn btn-sm bg-green">
                        Import Occupancy
                    </a>
                    <a href="#" class="btn btn-sm btn-themeGray" data-bs-dismiss="modal" aria-label="Close">Cancel</a>

                    <button type="submit" class="btn btn-sm btn-theme">Submit</button>
                </div>
            </form>

		</div>
	</div>
</div>

<div class="modal fade" id="Import-occupancymodal" tabindex="-1" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered  modal-xl">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="staticBackdropLabel">Import Occupancy</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
            <form id="ImportoccupancyForm" enctype="multipart/form-data">
                <?php echo csrf_field(); ?>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 ">
                            <div class="uploadFile-block">
                                <div class="uploadFile-btn">
                                    <a href="#" class="btn btn-themeBlue btn-sm">Upload File </a>
                                    <input type="file" class="fornm-control" name="importFile" id="importFile" accept=".xls,.xlsx"> <span class="req_span">*</span>
                                </div>
                            </div>
                            <br>
                            <div class="uploadFile-text" id="ImportOccupancy">Excel Only</div>


                        </div>
                        <div class="col-md-6 ">
                            <a href="<?php echo e(URL::asset('resorts_assets/demofiles/Occupancy.xls')); ?>" target="_blank" class="btn btn-theme btn-small Employeefile mt-2">Download</a>

                        </div>
                    </div>

                </div>
                <div class="modal-footer justify-content-end">

                    <a href="#" class="btn btn-sm btn-themeGray me-2 " data-bs-dismiss="modal" aria-label="Close">Cancel</a>

                    <button type="submit" class="btn btn-sm btn-theme">Submit</button>
                </div>
            </form>

		</div>
	</div>
</div>



<div class="modal fade" id="sendRequest-modal" tabindex="-1" aria-labelledby="sendRequest-modal" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered modal-xl">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="staticBackdropLabel">Request Manning</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
            <form id="RequestManning">
                <?php echo csrf_field(); ?>
                    <div class="modal-body">
                        <div class="employee-name-content">
                            <div class="row g-12">

                                <div class="col-sm-12">
                                    <div class="d-flex align-items-center employee-name-box">

                                        <textarea placeholder="Enter Request Manning" name="manningRequest" id="manningRequest"class="form-control" ><?php echo e($msg['msg1']); ?></textarea>
                                    </div>
                                </div>


                            </div>
                        </div>
                    </div>
                    <div class="modal-footer justify-content-end">
                        <a href="#" class="btn btn-sm btn-themeGray me-2 " data-bs-dismiss="modal" aria-label="Close">Cancel</a>

                        <button type="submit" class="btn btn-sm btn-theme">Submit</button>
                    </div>
            </form>

		</div>
	</div>
</div>




<div class="modal fade" id="sendReminder-modal" tabindex="-1" aria-labelledby="sendReminder-modal" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered modal-xl">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="staticBackdropLabel">Reminder</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
            <form id="ReminderRequestManning">
                <?php echo csrf_field(); ?>
                    <div class="modal-body">
                        <div class="employee-name-content">
                            <div class="row g-12">

                                <div class="col-sm-12">
                                    <div class="d-flex align-items-center employee-name-box">

                                            <textarea placeholder="Enter Reminder Request for Manning" name="ManningReminderRequest" id="ManningReminderRequest"class="form-control" ><?php echo e($msg['msg2']); ?></textarea>
                                    </div>
                                </div>


                            </div>
                        </div>
                    </div>
                    <div class="modal-footer justify-content-end">
                        <a href="#" class="btn btn-sm btn-themeGray me-2 " data-bs-dismiss="modal" aria-label="Close">Cancel</a>

                        <button type="submit" class="btn btn-sm btn-theme">Submit</button>
                    </div>
            </form>

		</div>
	</div>
</div>



<div class="modal fade" id="Pending-Department" tabindex="-1" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered modal-small">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="staticBackdropLabel">Pending Departments</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>

                <div class="modal-body">

                        <div class="row">
                            <table class="table">
                                <thead>
                                        <tr>
                                            <th>Sr No</th>
                                            <th>Department Name</th>
                                        </tr>
                                </thead>
                                <tbody Class="PendingDepartmentlist">

                                </tbody>

                            </table>
                        </div>
                </div>


		</div>
	</div>
</div>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('import-css'); ?>
<style>
    .pdf-header, .pdf-footer {
        display: none;
        background-color: #014653;
        color: white;
        padding: 10px;
    }

    @media  print {
        .pdf-header, .pdf-footer {
            display: block !important;
            position: fixed;
            left: 0;
            right: 0;
            z-index: 1000;
        }

        .pdf-header {
            top: 0;
        }

        .pdf-footer {
            bottom: 0;
        }

        .appendManningBudgetMontly {
            margin-top: 140px;
            margin-bottom: 80px;
        }

        /* Hide buttons and UI for print */
        #downloadManningBudget,
        .submitBtn,
        .currency-budget-icon {
            display: none !important;
        }
    }
</style>
<?php $__env->stopSection(); ?>
<?php $__env->startSection('import-scripts'); ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

<script>
    // Define the route using Laravel's route() helper
    const employeeNationalityDataUrl = <?php echo json_encode(route('employee.nationality.data'), 15, 512) ?>;

    // Function to fetch employee nationality data
    function fetchEmployeeData() {
        return fetch(employeeNationalityDataUrl)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok ' + response.statusText);
                }
                return response.json();
            })
            .then(data => {
                // Update the chart with fetched data
                myDoughnutChart.data.datasets[0].data = [data.local, data.expat];
                myDoughnutChart.data.labels = [`${data.local} Local Employees`, `${data.expat} Expat Employees`];
                myDoughnutChart.update();
            })
            .catch(error => console.error('Error fetching employee data:', error));
    }

    var ctx = document.getElementById('myDoughnutChart').getContext('2d');

    // Custom plugin to display percentage labels inside the doughnut chart
    const doughnutLabelsInside = {
        id: 'doughnutLabelsInside',
        afterDatasetsDraw: function (chart) {
            var ctx = chart.ctx;
            chart.data.datasets.forEach(function (dataset, i) {
                var meta = chart.getDatasetMeta(i);
                if (!meta.hidden) {
                    meta.data.forEach(function (element, index) {
                        var dataValue = dataset.data[index];

                        var total = dataset.data.reduce(function (acc, val) {
                            return acc + val;
                        }, 0);
                        var percentage = Math.round((dataValue / total) * 100) + '%';

                        var position = element.tooltipPosition();

                        // Slight inward offset to prevent overlap with tooltip
                        var offsetX = (position.x - chart.width / 2) * 0.05;
                        var offsetY = (position.y - chart.height / 2) * 0.05;

                        ctx.fillStyle = '#fff';
                        ctx.font = 'bold 16px Poppins';
                        ctx.textAlign = 'center';
                        ctx.textBaseline = 'middle';

                        ctx.fillText(percentage, position.x - offsetX, position.y - offsetY);
                    });
                }
            });
        }
    };
    // Initialize the Doughnut chart
    var myDoughnutChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Loading...', 'Loading...'], // Placeholder labels
            datasets: [{
                data: [0, 0], // Placeholder data
                backgroundColor: ['#014653', '#2EACB3'],
                hoverOffset: 10, // Initial hover offset
            }]
        },
        options: {
            responsive: true,
            plugins: {
                doughnutLabelsInside: true, // Enable custom plugin
                legend: {
                    display: false
                },
                tooltip: {
                    usePointStyle: true
                }
            },
            layout: {
                padding: {
                    top: 10,
                    bottom: 10,
                    left: 20,
                    right: 20
                }
            },
            hover: {
                onHover: function (event, activeElements) {
                    if (activeElements.length > 0) {
                        const chartSegment = activeElements[0];
                        chartSegment.element.options.hoverOffset = 100;
                    } else {
                        myDoughnutChart.data.datasets[0].hoverOffset = 10;
                    }
                    myDoughnutChart.update();
                }
            },
            hoverOffset: 30
        },
        plugins: [doughnutLabelsInside]
    });

    // Fetch data and update the chart
    fetchEmployeeData();
</script>
<script>
    document.getElementById('downloadManningBudget').addEventListener('click', function () {
        // Expand all accordions before PDF
        document.querySelectorAll('.accordion-collapse').forEach(section => {
            section.classList.add('show');
        });

        document.querySelectorAll('.accordion-button').forEach(btn => {
            btn.classList.remove('collapsed');
            btn.setAttribute('aria-expanded', 'true');
        });

        const element = document.getElementById('pdf-wrapper');

        html2pdf().set({
            margin: 0.5,
            filename: 'manning-budget.pdf',
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: { scale: 2 },
            jsPDF: { unit: 'in', format: 'a4', orientation: 'portrait' },
            pagebreak: { mode: ['avoid-all', 'css', 'legacy'] }
        }).from(element).save();
    });
</script>
<script type="module">
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


    var ctz = document.getElementById('myLineChart').getContext('2d');
  
    // Function to fetch data dynamically via AJAX
    function fetchAIInsightsData() {
        const upcomingMonths = [];
        const currentDate = new Date();
        for (let i = 0; i < 5; i++) {
            const nextMonth = new Date(currentDate.getFullYear(), currentDate.getMonth() + i, 1);
            const monthYear = nextMonth.toLocaleString('default', { month: 'short', year: 'numeric' });
            upcomingMonths.push(monthYear);
        }

        $.ajax({
            url: <?php echo json_encode(route('resort.workforceplan.dashboard.ai-insights'), 15, 512) ?>,
            method: 'GET',
            data: { months: upcomingMonths },
            success: function (response) {
                myLineChart.data.labels = upcomingMonths;
                myLineChart.data.datasets[0].data = response.occupancyRates;
                myLineChart.data.datasets[1].data = response.hiringData;
                myLineChart.update();
            },
            error: function (error) {
                console.error('Error fetching AI Insights data:', error);
            }
        });
    }

    var myLineChart = new Chart(ctz, {
        type: 'line',
        data: {
            labels: [], // Placeholder  dynamic labels
            datasets: [
                {
                    label: 'Occupancy Rates',
                    data: [], // Placeholder dynamic data
                    borderColor: '#014653',
                    backgroundColor: '#014653',
                    borderWidth: 1,
                    fill: false,
                    tension: 0.4,
                    pointRadius: 0
                },
                {
                    label: 'Hiring Data',
                    data: [], // Placeholder dynamic data
                    borderColor: '#DFFF00',
                    backgroundColor: '#DFFF00',
                    borderWidth: 1,
                    fill: false,
                    tension: 0.4,
                    pointRadius: 0
                }
            ]
        },
        options: {
            plugins: {
                legend: {
                    display: false
                }
            },
            layout: {
                padding: {
                    top: 0,
                    bottom: 0,
                    left: 0,
                    right: 0
                }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    grid: {
                        display: false
                    },
                    border: {
                        display: true
                    }
                },
                y: {
                    grid: {
                        display: false
                    },
                    beginAtZero: true,
                    ticks: {
                        stepSize: 5
                    }
                }
            }
        }
    });

    $(document).ready(function () {
        // Fetch AI Insights data on page load
        fetchAIInsightsData();
    });

</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('resorts.layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\wamp64\www\Wisdom-Ai\HRVMS-Wisdom-AI\resources\views/resorts/workforce_planning/dashboard.blade.php ENDPATH**/ ?>