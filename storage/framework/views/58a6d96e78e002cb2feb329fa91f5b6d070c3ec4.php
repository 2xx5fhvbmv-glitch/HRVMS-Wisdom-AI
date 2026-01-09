
<?php $__env->startSection('page_tab_title' ,"HOD-Dashboard"); ?>

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
                            <h1>HOD Dashboard</h1>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row g-4">
                <div class="col-xl-8 col-lg-7">
                    <div class="row g-4 mb-30">
                        <div class="col-md-6">
                            <div class="card card-hod ">
                                <div class="">
                                    <div class="card-title">
                                        <h3>Total Employees</h3>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <strong><?php echo e($totalemployees); ?></strong>
                                        <div class="user-ovImg ms-xxl-4 ms-xl-3 ms-2">
                                            <?php if($employees->isNotEmpty()): ?>
                                                <?php $__currentLoopData = $employees; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $emp): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                    <div class="img-circle">
                                                        <img src="<?php echo e(Common::getResortUserPicture($emp->Admin_Parent_id)); ?>" alt="image">
                                                    </div>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                            <?php endif; ?>
                                            <?php if($LeftemployeesCount > 0 ): ?>
                                                <div class="num"><?php echo e($LeftemployeesCount); ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <a href="<?php echo e(route('resort.employeelist')); ?>">
                                        <img src="<?php echo e(URL::asset('resorts_assets/images/arrow-right-circle.svg')); ?>" alt="" class="img-fluid">
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card card-hod ">
                                <div class="">
                                    <div class="card-title">
                                        <h3>Positions</h3>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <strong><?php echo e($resort_positions_count); ?></strong>
                                    </div>
                                </div>
                                <div>
                                    <a href="<?php echo e(route('resort.manning.positions')); ?>">
                                        <img src="<?php echo e(URL::asset('resorts_assets/images/arrow-right-circle.svg')); ?>" alt="" class="img-fluid">
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card  mb-30">
                        <div class="card-title">
                            <div class="row justify-content-between align-items-center g-3">
                                <div class="col">
                                    <h3><?php echo e($department_details[0]->name); ?></h3>
                                </div>
                                <div class="col-auto">
                                    <div class="form-group">
                                        <?php $Currentyear = date('Y');?>
                                        <select class="form-select HodDataset" aria-label="Default select example ">
                                            <?php for($i = 0; $i < 2; $i++): ?>
                                                <?php  $year1 = date('Y') + $i; ?>
                                                <option value="<?php echo e($year1); ?>" >Jan <?php echo e($year1); ?> - Dec <?php echo e($year1); ?></option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-collapse table-food">
                                <thead>
                                    <tr>
                                        <th>Positions </th>
                                        <th>No. of Vacancy</th>
                                        <th>Rank</th>
                                        <th>Nation</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody class="DepartmentWisePositions">
                                    <?php if($vacant_positions): ?>
                                        <?php $__currentLoopData = $vacant_positions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $pos): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <tr>
                                                <td><?php echo e($pos->position_title); ?></td>
                                                <td> <?php echo e($pos->headcount ?? '00'); ?> <span class="badge bg-vac"><?php echo e($pos->vacantcount ?? '00'); ?> Vacant Available</span></td>
                                                <td></td>
                                                <td></td>
                                                <td>
                                                    <button class="table-icon collapsed" data-bs-toggle="collapse"
                                                        data-bs-target="#collapse-<?php echo e($pos->id); ?>" aria-expanded="false"
                                                        aria-controls="collapse-<?php echo e($pos->id); ?>">
                                                        <i class="fa-solid fa-angle-down"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            <!-- Collapsed row for employees -->
                                            <?php if($pos->employees && count($pos->employees) > 0): ?>
                                                <?php $__currentLoopData = $pos->employees; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $employee): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                    <tr class="collapse" id="collapse-<?php echo e($pos->id); ?>">
                                                        <td></td>
                                                        <td>
                                                            <div class="user-block">
                                                                <div class="img-circle">
                                                                    <img src="<?php echo e(Common::getResortUserPicture($employee->Admin_Parent_id)); ?>" alt="image">
                                                                </div>
                                                                <h6><?php echo e($employee->first_name); ?> <?php echo e($employee->last_name); ?></h6>
                                                            </div>
                                                        </td>
                                                        <td><?php


                                                        $Rank = config( 'settings.Position_Rank');


                                                        $AvilableRank = array_key_exists($employee->rank, $Rank) ? $Rank[$employee->rank] : '';

                                                    ?>
                                                    <?php echo e($AvilableRank); ?>

                                                </td>
                                                        <td><?php echo e($employee->nationality); ?></td>
                                                        <td></td>
                                                    </tr>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                            <?php endif; ?>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-xl-4 col-lg-5">
                    <div class="card budget-e-box mb-30 AppendLifeCycleofRequest AppendRequestManningRequest">
                        <?php if( isset($getNotifications) && isset($getNotifications->loginid)): ?>
                            <div class="card-title d-flex justify-content-between">
                                <h3>Requests</h3>
                            </div>
                            <div class="requestsUser-block ">
                                <div class="">
                                    <div class="img-circle">
                                    <img src="<?php echo e(Common::getResortUserPicture($getNotifications->loginid)); ?>" alt="image">
                                    </div>
                                    <div class="">
                                        <h6><?php echo e($getNotifications->first_name); ?><?php echo e($getNotifications->middle_name); ?></h6></h6>
                                        <p><?php echo e(strtoupper($getNotifications->DepartmentName)); ?></p>
                                    </div>
                                </div>
                                <div class="dfs">
                                    <input type="hidden" name="message_id" id="message_id" value="<?php echo e((isset( $getNotifications->message_id)?   $getNotifications->message_id :'')); ?>">
                                    <h5><?php echo e((isset($getNotifications->reminder_message_subject )) ? $getNotifications->reminder_message_subject : $getNotifications->message_subject); ?></h5>
                                </div>
                            </div>
                            <div class="text-center">
                                <a href="#sendRespond-modal" data-bs-toggle="modal" class="btn btn-sm btn-theme">Send
                                    Respond</a>
                            </div>
                        <?php elseif(!empty($BudgetStatus) && count($BudgetStatus)): ?>
                            <div class="card-title d-flex justify-content-between">
                                <h3>Manning <?php echo e((isset($Year)) ? date('Y')+1 : date('Y')+1); ?></h3>
                            </div>
                            <ul class="manning-timeline">
                                <?php
                                    // Defined all the possible steps for budget approval in sitesetting Array
                                    $allSteps = config('settings.manningRequestLifeCycle');
                                ?>
                                <?php if(!empty($allSteps)): ?>
                                    <?php $__currentLoopData = $allSteps; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $stepKey => $stepName): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <li class="<?php if(array_key_exists($stepKey, $BudgetStatus) && $BudgetStatus[$stepKey]['comments'] == $stepName): ?>
                                                active
                                            <?php else: ?>
                                                complete
                                            <?php endif; ?>">
                                            <span><?php echo e($stepName); ?> </span>
                                        </li>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                <?php endif; ?>
                            </ul>
                        <?php elseif($BudgetRejactedStatus): ?>
                            <div class="card-title d-flex justify-content-between">
                                <h3>Requests</h3>
                            </div>
                            <div class="requestsUser-block ">
                                <div class="">
                                    <div class="img-circle">
                                    <img src="<?php echo e(Common::getResortUserPicture($BudgetRejactedStatus->loginid)); ?>" alt="image">
                                    </div>
                                    <div class="">
                                        <h6><?php echo e($BudgetRejactedStatus->first_name); ?><?php echo e($BudgetRejactedStatus->middle_name); ?></h6></h6>
                                        <p><?php echo e(strtoupper($BudgetRejactedStatus->DepartmentName)); ?></p>
                                    </div>
                                </div>
                                <div class="dfs">
                                    <input type="hidden" name="budget" id="budget" value="<?php echo e($BudgetRejactedStatus->Budget_id); ?>">

                                    <input type="hidden" name="BudgetRejacted_message_id" id="BudgetRejacted_message_id" value="<?php echo e((isset( $BudgetRejactedStatus->message_id)?   $BudgetRejactedStatus->message_id :'')); ?>">
                                    <h5><?php echo e((isset($BudgetRejactedStatus->reminder_message_subject )) ? $BudgetRejactedStatus->reminder_message_subject : $BudgetRejactedStatus->message_subject); ?></h5>
                                </div>
                            </div>
                            <div class="text-center">
                                <a href="#sendRespond-modal" data-message_id = "<?php echo e((isset($BudgetRejactedStatus->message_id ) ? $BudgetRejactedStatus->message_id :'')); ?>" data-Budget_id="<?php echo e((isset($BudgetRejactedStatus->Budget_id ) ? $BudgetRejactedStatus->Budget_id :'')); ?>" data-bs-toggle="modal" class="btn btn-sm btn-theme">Revise
                                    Respond</a>
                            </div>
                        <?php else: ?>
                            <p>No Requests</p>
                        <?php endif; ?>
                    </div>
                    <div class="card  mb-30">
                        <div class="card-title d-flex justify-content-between">
                            <h3>Vacant</h3>
                        </div>
                        <div class="table-responsive">
                            <table class="table  w-100">
                                <thead>
                                    <tr>
                                        <th>Positions</th>
                                        <th class="text-nowrap">No. of Vacancy</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if($vacant_positions): ?>
                                        <?php $__currentLoopData = $vacant_positions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $pos): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <tr>
                                                <td><?php echo e($pos->position_title); ?></td>
                                                <td> <?php echo e($pos->vacantcount ?? 00); ?></td>
                                            </tr>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <!-- <div class="card  ">
                        <div class="card-title d-flex justify-content-between">
                            <h3>AI Insights</h3>
                        </div>
                        <p class="chart-title">Expected Staffing Needs</p>
                        <canvas id="myLineChart" class="mb-3"></canvas>
                        <div class="row g-2">
                            <div class="col-auto">
                                <div class="doughnut-label">
                                    <span class="bg-theme"></span>Occupancy Rates
                                </div>
                            </div>
                            <div class="col-auto">
                                <div class="doughnut-label">
                                    <span class="bg-themeLightBlue"></span>Seasonal Data
                                </div>
                            </div>
                            <div class="col-auto">
                                <div class="doughnut-label">
                                    <span class="bg-themeNeon"></span>Hiring Data
                                </div>
                            </div>
                        </div>
                    </div> -->
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="sendRespond-modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="staticBackdropLabel">Respond</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="manningResponseForm" method="POST" action="<?php echo e(route('manning.responses.store')); ?>">
                    <?php echo csrf_field(); ?>

                    <div class="modal-body">
                        <div class="form-check mb-3 fw-500">
                            <input class="form-check-input" type="checkbox" value="" id="flexCheckChecked">
                            <label class="form-check-label" for="flexCheckChecked">
                                Same As This Year
                            </label>
                        </div>
                        <?php
                            $currentYear = date('Y'); // Get the current year
                            $nextYear = $currentYear + 1; // Calculate the next year
                            $months = [
                                'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'
                            ]; // List of months
                        ?>
                        <input type="hidden" name="resort_id" id="resort_id" value="<?php echo e($resort_id); ?>">
                        <input type="hidden" name="dept_id" id="dept_id" value="<?php echo e($Dept_id); ?>">
                        <input type="hidden" name="year" id="year" value="<?php echo e($nextYear); ?>">
                        <input type="hidden" name="total_headcount" id="total_headcount">
                        <input type="hidden" name="total_vacant_headcount" id="total_vacant_headcount">
                        <input type="hidden" name="total_filled_headcount" id="total_filled_headcount">
                        <input type="hidden" name="total_headcount_current_year" id="total_headcount_current_year" value="0">
                        <input type="hidden" name="message_id" id="Submit_message_id" value="<?php echo e((isset( $getNotifications->message_id)?   $getNotifications->message_id :'')); ?>">
                        <input type="hidden" name="Budget_id" id="Budget_id" value="">
                        <div class="position-count-summary">
                            <h4>Position Counts</h4>
                            <div>
                                Total Filled Positions:
                                <span id="overall_filled_positions" class="badge bg-info">0</span>
                            </div>
                            <div>
                                Total Vacant Positions:
                                <span id="overall_vacant_positions" class="badge bg-info">0</span>
                            </div>
                            <div>
                                Total Headcount:
                                <span id="total-headcount" class="badge bg-info">0</span>
                            </div>
                        </div>
                        <div class="card">
                            <div class="table-responsive">
                                <table class="table table-collapse table-respond">
                                    <thead>
                                        <tr>
                                            <th>Positions</th>
                                            <?php $__currentLoopData = $months; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $month): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <th><?php echo e($month); ?> <?php echo e($nextYear); ?>

                                                    <?php if($index > 0): ?> <!-- Show copy icon starting from the second month -->
                                                        <span data-bs-toggle="tooltip" data-bs-placement="bottom" title="Copy from <?php echo e($months[$index-1]); ?> <?php echo e($nextYear); ?>">
                                                            <img src="<?php echo e(URL::asset('resorts_assets/images/copy.svg')); ?>" alt="icon" onclick="copyColumn(<?php echo e($index); ?>, <?php echo e($index + 1); ?>)">
                                                        </span>
                                                    <?php endif; ?>
                                                </th>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if(isset($positions) && $positions->count() > 0): ?>
                                            <?php $__currentLoopData = $positions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $pos): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <input type="hidden" name="positions[]" id="pos-<?php echo e($pos->id); ?>" value="<?php echo e($pos->id); ?>">
                                                <tr>
                                                    <td>
                                                        <?php echo e($pos->position_title); ?> (<?php echo e($pos->no_of_positions); ?>)
                                                        <button type="button" class="table-icon collapsed ms-2" data-bs-toggle="collapse" data-bs-target="#collapse-<?php echo e($pos->id); ?>" aria-expanded="false" aria-controls="collapse-<?php echo e($pos->id); ?>" data-position-id="<?php echo e($pos->id); ?>">
                                                            <i class="fa-solid fa-angle-down"></i>
                                                        </button>

                                                    </td>
                                                    <!-- 12 months (columns) -->
                                                    <?php for($i = 0; $i < 12; $i++): ?>
                                                        <?php
                                                            $monthName = DateTime::createFromFormat('!m', $i + 1)->format('F');
                                                        ?>
                                                        <td>
                                                            <div class="input-group inputCounter-group">
                                                                <span class="input-group-btn">
                                                                    <button type="button" class="btn btn-number" data-type="minus" disabled="disabled" onclick="decrementValue(this)">
                                                                        <i class="fa-solid fa-minus"></i>
                                                                    </button>
                                                                </span>

                                                                <input type="hidden" id="filled_positions_<?php echo e($pos->id); ?>_<?php echo e($i); ?>" name="filled_positions[<?php echo e($pos->id); ?>][<?php echo e($i); ?>]" value="0">

                                                                <input type="hidden" id="vacant_positions_<?php echo e($pos->id); ?>_<?php echo e($i); ?>" name="vacant_positions[<?php echo e($pos->id); ?>][<?php echo e($i); ?>]" value="0">

                                                                <input type="text" class="form-control input-number" name="monthly_data[<?php echo e($pos->id); ?>][<?php echo e($i); ?>]" id="count-<?php echo e($pos->id); ?>-<?php echo e($i); ?>" value="0" min="0" max="10" data-month="<?php echo e($monthName); ?>" data-month-index="<?php echo e($i); ?>" data-position-id="<?php echo e($pos->id); ?>">
                                                                <span class="input-group-btn">
                                                                    <button type="button" class="btn btn-number" data-type="plus" onclick="incrementValue(this)">
                                                                        <i class="fa-solid fa-plus"></i>
                                                                    </button>
                                                                </span>
                                                            </div>
                                                        </td>
                                                    <?php endfor; ?>
                                                </tr>
                                                <!-- Hidden details for positions -->
                                                <tr class="collapse" id="collapse-<?php echo e($pos->id); ?>">
                                                    <td><span class="badge-headcount" id="head-count">2024 HEADCOUNT = 00 <br/> 2025 HEADCOUNT <br/> 2025 Filled COUNT <br/> 2025 vacant count </span></td>
                                                    <td id="january-<?php echo e($pos->id); ?>" data-month="January"></td>
                                                    <td id="february-<?php echo e($pos->id); ?>" data-month="February"></td>
                                                    <td id="march-<?php echo e($pos->id); ?>" data-month="March"></td>
                                                    <td id="april-<?php echo e($pos->id); ?>" data-month="April"></td>
                                                    <td id="may-<?php echo e($pos->id); ?>" data-month="May"></td>
                                                    <td id="june-<?php echo e($pos->id); ?>" data-month="June"></td>
                                                    <td id="july-<?php echo e($pos->id); ?>" data-month="July"></td>
                                                    <td id="august-<?php echo e($pos->id); ?>" data-month="August"></td>
                                                    <td id="september-<?php echo e($pos->id); ?>" data-month="September"></td>
                                                    <td id="october-<?php echo e($pos->id); ?>" data-month="October"></td>
                                                    <td id="november-<?php echo e($pos->id); ?>" data-month="November"></td>
                                                    <td id="december-<?php echo e($pos->id); ?>" data-month="December"></td>
                                                </tr>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="13">No positions available.</td>
                                                </tr>
                                            <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer justify-content-start">
                        <button type="button" class="btn btn-themeBlue" id="saveDraftBtn">Save As Draft</button>
                        <button type="button" class="btn btn-sm btn-themeGray me-2" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-theme">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- modal -->

    <div class="modal fade" id="Manning-modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-manning">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <!-- <h5 class="modal-title" id="staticBackdropLabel">Manning has been sent!</h5> -->
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <h4>Manning has been sent!</h4>
                    <div class="row g-2 justify-content-center">
                        <div class="col-auto"><span class="manningHeadcount-block">2024 headcount = 00</span></div>
                        <div class="col-auto"><span class="manningHeadcount-block">2025 headcount = 23</span></div>
                    </div>
                </div>

            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('import-css'); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('import-scripts'); ?>

    <script type="text/javascript">
        $(document).ready(function(){
            const currentYear = new Date().getFullYear(); // e.g., 2024

            // Calculate the next year
            const nextYear = currentYear + 1; // e.g., 2025
            var  resort_id  = "<?php echo e(Auth::guard('resort-admin')->user()->resort_id); ?>";
            var  Position_id  = "<?php echo e(Auth::guard('resort-admin')->user()->GetEmployee->Position_id); ?>";
            var  Dept_id  = "<?php echo e(Auth::guard('resort-admin')->user()->GetEmployee->Dept_id); ?>";
            var year = nextYear;

            // console.log(resort_id,Dept_id,year);

            $(document).on('change', '.HodDataset', function () {

                $.post('<?php echo e(route("hod.getYearBasePositions")); ?>',
                {"_token": "<?php echo e(csrf_token()); ?>",
                    "year": $(this).val(),
                    "ResortId":resort_id,
                    "Position_id":Position_id,
                    "Dept_id":Dept_id
                },function (response) {

                    if(response.success == true)
                    {
                        $(".DepartmentWisePositions").html(response.html);
                    }
                    else
                    {
                        $(".DepartmentWisePositions").html(response.html);
                    }

                });
            });

            $(".table-icon").click(function () {
                $(this).parents('tr').toggleClass("in");
            });


            $('#sendRespond-modal').on('show.bs.modal', function (e) {
                // Get the necessary data attributes or values to pass to fetchDraftData


                $("#Budget_id").val($("#budget").val());

            $("#Submit_message_id").val($("#BudgetRejacted_message_id").val());

                fetchDraftData(resort_id, Dept_id, year);


            });


        });
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });
    </script>
    <script type="module">
        var ctz = document.getElementById('myLineChart').getContext('2d');
        var myLineChart = new Chart(ctz, {
            type: 'line',
            data: {
                labels: ['', 'Sep 2024', 'Oct 2024', 'Nov 2024', 'Dec 2024'], // X-axis labels
                datasets: [
                    {
                        label: 'Occupancy Rates',
                        data: [7, 10, 15, 20, 30],
                        borderColor: '#014653',
                        backgroundColor: '#014653',
                        borderWidth: 1,
                        fill: false,
                        tension: 0.4, // Creates smooth curves
                        // cubicInterpolationMode: 'monotone', // Monotone interpolation
                        pointRadius: 0 // Remove dots
                    },
                    {
                        label: 'Seasonal Data',
                        data: [4, 7, 20, 35, 25], // Data points for the dataset
                        borderColor: ' #4C88BB', // Line color
                        backgroundColor: '#4C88BB',
                        borderWidth: 1,
                        fill: false,
                        tension: 0.4, // Default cubic Bézier curve (smooth curve)
                        pointRadius: 0 // Remove dots
                    },
                    {
                        label: 'Hiring Data',
                        data: [10, 8, 6, 15, 30], // Data points for the dataset
                        borderColor: '#DFFF00 ', // Line color
                        backgroundColor: '#DFFF00', // Fill color under the line
                        borderWidth: 1, // Line width
                        fill: false,
                        tension: 0.4, // No tension for stepped lines
                        pointRadius: 0 // Remove dots
                        // stepped: true // Stepped line interpolation
                    }
                ]
            },
            options: {
                plugins: {
                    doughnutLabelsInside: true, // Enable the custom plugin
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
                        beginAtZero: true, // Start x-axis at zero
                        grid: {
                            display: false // Hide grid lines on the x-axis
                        },
                        border: {
                            display: true // Hide the x-axis border
                        }
                    },
                    y: {
                        grid: {
                            display: false // Hide grid lines on the y-axis
                        },
                        beginAtZero: true,
                        ticks: {
                            stepSize: 5,
                        }
                    }
                }
            }
        });
    </script>
    <script>
        // Global object to store headcounts per position and month
        let headcounts = {}; // headcounts[positionId][monthName] = count
        let filledCount = 0;
        let vacantCount = 0;
        let totalFilledCount = 0;
        let totalVacantCount = 0;
        // Global variables for overall totals
        let overallFilledCount = 0;
        let overallVacantCount = 0;
        const currentYear = new Date().getFullYear(); // e.g., 2024
        const nextYear = currentYear + 1; // e.g., 2025
        $('#manningResponseForm').submit(function(e) {
            e.preventDefault();
            $("#Budget_id").val($("#Budget_id").val());

            let BudgetRejacted_message_id = $("#BudgetRejacted_message_id").val();

            if (BudgetRejacted_message_id !== null && BudgetRejacted_message_id !== undefined && BudgetRejacted_message_id.trim() !== "") {


                $("#Submit_message_id").val(BudgetRejacted_message_id);
            }
            else
            {
                $("#Submit_message_id").val($("#message_id").val());

            }



            // Serialize form data
            let formData = $(this).serialize();
            $.ajax({
                url: "<?php echo e(route('manning.responses.store')); ?>",
                type: "POST",
                data: formData,
                success: function(response) {
                    if(response.success) {
                        // Hide modal and show success message
                        $('#sendRespond-modal').modal('hide');

                        // Update headcount display dynamically
                        document.querySelector('.manningHeadcount-block').innerText = `${currentYear} headcount = ${response.currentYearHeadcount}`;
                        document.querySelectorAll('.manningHeadcount-block')[1].innerText = `${nextYear} headcount = ${response.nextYearHeadcount}`;

                        // Show the success modal
                        $('#Manning-modal').modal('show');
                        $('#total_headcount_current_year').val(response.currentYearHeadcount);

                        $('.AppendLifeCycleofRequest').html(response.html);

                        // Reset form fields
                        // $('#sendRespond-modal')[0].reset();

                        // Display success notification
                        toastr.success(response.msg, "Success", {
                            positionClass: 'toast-bottom-right'
                        });
                    } else {
                        // Handle validation or other errors
                        toastr.error(response.msg, "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                },
                error: function(xhr) {
                    // Handle server-side error (status code 500 or validation errors)
                    let errorMessage = xhr.responseJSON.message || "An error occurred!";
                    toastr.error(errorMessage, "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                }
            });
        });

        // Call this function on page load if needed to set initial state of minus button
        document.addEventListener("DOMContentLoaded", function() {
            document.querySelectorAll('.input-number').forEach(function(input) {
                updateMinusButtonState(input);
            });
        });

        // Function to handle input changes
        function handleInputChange(input) {
            let currentValue = parseInt(input.value);
            let maxValue = parseInt(input.getAttribute('max'));

            // Validate input value
            if (isNaN(currentValue) || currentValue < 0) {
                input.value = 0; // Reset to 0 if the value is invalid
                currentValue = 0;
            } else if (currentValue > maxValue) {
                input.value = maxValue; // Cap at max value
                currentValue = maxValue;
            }

            let positionId = input.getAttribute('data-position-id');
            let monthName = input.getAttribute('data-month').toLowerCase();
            let monthIndex = input.getAttribute('data-month-index');

            // Make an AJAX request to get employee data
            $.ajax({
                url: '<?php echo e(route('manning.fetch.employees')); ?>',
                type: 'POST',
                data: {
                    position_id: positionId,
                    count: currentValue // Use the updated count
                },
                success: function(response) {
                    let monthCell = document.querySelector(`#${monthName}-${positionId}`);

                    let filledCount = 0;
                    let vacantCount = 0;

                    // Process the employee data response
                    if (Array.isArray(response) && response.length > 0) {
                        response.forEach(employee => {
                            if (employee.name === "Vacant") {
                                vacantCount++;
                            } else {
                                filledCount++;
                            }
                        });

                        monthCell.innerHTML = response.map((employee, index) =>
                            employee.name === "Vacant" ? '<div><span class="badge bg-vac">Vacant</span></div>' : `<div>${index + 1}. ${employee.name}</div>`
                        ).join('');
                    } else {
                        monthCell.innerHTML = '<div><span class="badge bg-vac">Vacant</span></div>';
                        vacantCount = currentValue; // Assume all positions are vacant
                    }

                    // Update the hidden fields for filled and vacant positions
                    let filledInput = document.querySelector(`#filled_positions_${positionId}_${monthIndex}`);
                    let vacantInput = document.querySelector(`#vacant_positions_${positionId}_${monthIndex}`);

                    if (filledInput) {
                        filledInput.value = filledCount;
                    } else {
                        console.error(`Filled positions input not found for position ID: ${positionId} and month index: ${monthIndex}`);
                    }

                    if (vacantInput) {
                        vacantInput.value = vacantCount;
                    } else {
                        console.error(`Vacant positions input not found for position ID: ${positionId} and month index: ${monthIndex}`);
                    }

                    // Update the headcount for the month
                    updateHeadcountForMonth(positionId, monthName, currentValue, filledCount, vacantCount);
                },
                error: function(xhr, status, error) {
                    console.error('Error fetching employees:', error);
                }
            });

            updateMinusButtonState(input); // Ensure the state of the minus button is updated
        }

        function copyColumn(fromIndex, toIndex) {
            // Get all rows in the tbody
            let rows = document.querySelectorAll('tbody tr:not(.collapse)'); // Select non-collapsed rows

            rows.forEach(row => {
                // Get all columns (td) within each row
                let cells = row.querySelectorAll('td');
                // Ensure that both the source and target columns exist before proceeding
                if (cells[fromIndex] && cells[toIndex]) {
                    // Find the input field in the source column (fromIndex)
                    let sourceInput = cells[fromIndex].querySelector('input.input-number');
                    // Find the input field in the target column (toIndex)
                    let targetInput = cells[toIndex].querySelector('input.input-number');

                    // If both inputs exist, copy the value from the source to the target
                    if (sourceInput && targetInput) {
                        // Copy value from source to target
                        targetInput.value = sourceInput.value;

                        // Get position ID and month names from data attributes
                        let positionId = targetInput.getAttribute('data-position-id');
                        let fromMonth = sourceInput.getAttribute('data-month').toLowerCase();
                        let toMonth = targetInput.getAttribute('data-month').toLowerCase();
                        let monthIndex = targetInput.getAttribute('data-month-index');

                        // Update headcounts global object
                        if (!headcounts[positionId]) {
                            headcounts[positionId] = {};
                        }
                        headcounts[positionId][toMonth] = parseInt(targetInput.value);

                        // Make an AJAX request to get employee data for the target month (new month)
                        $.ajax({
                            url: '<?php echo e(route('manning.fetch.employees')); ?>',
                            type: 'POST',
                            data: {
                                position_id: positionId,
                                count: targetInput.value
                            },
                            success: function(response) {
                                filledCount = 0;
                                vacantCount = 0;
                                // Find the correct month cell based on the position ID and target month
                                let targetMonthCell = document.querySelector(`#${toMonth}-${positionId}`);

                                if (Array.isArray(response) && response.length > 0) {
                                    response.forEach(employee => {
                                        if (employee.name === "Vacant") {
                                            vacantCount++;
                                        } else {
                                            filledCount++;
                                        }
                                    });
                                    // If employees are found, display their names or "Vacant"
                                    let employeeNames = response.map((employee, index) => {
                                        if (employee.name === "Vacant") {
                                            return `<div><span class="badge bg-vac">Vacant</span></div>`;
                                        }
                                        return `<div>${index + 1}. ${employee.name}</div>`;
                                    }).join('');

                                    // Update the target month cell with employee names or vacant badges
                                    targetMonthCell.innerHTML = employeeNames;
                                } else {
                                    // If no employees found, display "Vacant"
                                    targetMonthCell.innerHTML = `<div><span class="badge bg-vac">Vacant</span></div>`;
                                }

                                // Expand the collapsed row for the specific position
                                $(`#collapse-${positionId}`).collapse('show');
                                let filledInput = document.querySelector(`#filled_positions_${positionId}_${monthIndex}`);
                                let vacantInput = document.querySelector(`#vacant_positions_${positionId}_${monthIndex}`);

                                if (filledInput) {
                                    filledInput.value = filledCount;
                                } else {
                                    console.error(`Filled positions input not found for position ID: ${positionId} and month index: ${monthIndex}`);
                                }

                                if (vacantInput) {
                                    vacantInput.value = vacantCount;
                                } else {
                                    console.error(`Vacant positions input not found for position ID: ${positionId}`);
                                }

                                // Recalculate and update headcount for the affected position
                                // updateHeadcountForMonth(positionId, toMonth, parseInt(targetInput.value));
                                updateHeadcountForMonth(positionId, toMonth, parseInt(targetInput.value),filledCount,vacantCount);
                            },
                            error: function(xhr, status, error) {
                                console.error('Error fetching employees:', error);
                            }
                        });
                    }
                }
            });

            // After copying is done, recalculate the total headcount across all positions
            updateTotalHeadcount();

            // Update the minus button state for each target input
            rows.forEach(row => {
                let cells = row.querySelectorAll('td');
                if (cells[toIndex]) {
                    let targetInput = cells[toIndex].querySelector('input.input-number');
                    if (targetInput) {
                        updateMinusButtonState(targetInput);
                    }
                }
            });

            event.stopPropagation();
        }

        function incrementValue(button) {
            let inputGroup = button.closest('.inputCounter-group');
            let input = inputGroup.querySelector('.input-number');
            let currentValue = parseInt(input.value);
            let maxValue = parseInt(input.getAttribute('max'));

            let positionId = input.getAttribute('data-position-id');
            let monthName = input.getAttribute('data-month').toLowerCase();
            let monthIndex = input.getAttribute('data-month-index'); // Get the month index here

            if (currentValue < maxValue) {
                input.value = currentValue + 1;

                // Make an AJAX request to get employee data
                $.ajax({
                    url: '<?php echo e(route('manning.fetch.employees')); ?>',
                    type: 'POST',
                    data: {
                        position_id: positionId,
                        count: input.value
                    },
                    success: function(response) {
                        let monthCell = document.querySelector(`#${monthName}-${positionId}`);

                        let filledCount = 0;
                        let vacantCount = 0;

                        if (Array.isArray(response) && response.length > 0) {
                            response.forEach(employee => {
                                if (employee.name === "Vacant") {
                                    vacantCount++;
                                } else {
                                    filledCount++;
                                }
                            });

                            monthCell.innerHTML = response.map((employee, index) =>
                                employee.name === "Vacant" ? '<div><span class="badge bg-vac">Vacant</span></div>' : `<div>${index + 1}. ${employee.name}</div>`
                            ).join('');
                        } else {
                            monthCell.innerHTML = '<div><span class="badge bg-vac">Vacant</span></div>';
                            vacantCount = input.value; // Assume all positions are vacant
                        }

                        let filledInput = document.querySelector(`#filled_positions_${positionId}_${monthIndex}`);
                        let vacantInput = document.querySelector(`#vacant_positions_${positionId}_${monthIndex}`);

                        if (filledInput) {
                            filledInput.value = filledCount;
                        } else {
                            console.error(`Filled positions input not found for position ID: ${positionId} and month index: ${monthIndex}`);
                        }

                        if (vacantInput) {
                            vacantInput.value = vacantCount;
                        } else {
                            console.error(`Vacant positions input not found for position ID: ${positionId}`);
                        }

                        event.stopPropagation();
                        updateHeadcountForMonth(positionId, monthName, parseInt(input.value),filledCount,vacantCount);
                    },
                    error: function(xhr, status, error) {
                        console.error('Error fetching employees:', error);
                    }
                });
            }

            updateMinusButtonState(input);
            event.stopPropagation();
        }

        // Function to decrement headcount
        function decrementValue(button) {
            let inputGroup = button.closest('.inputCounter-group');
            let input = inputGroup.querySelector('.input-number');
            let currentValue = parseInt(input.value);

            if (currentValue > 1) {
                input.value = currentValue - 1;

                let positionId = input.getAttribute('data-position-id');
                let monthName = input.getAttribute('data-month').toLowerCase();
                let monthIndex = input.getAttribute('data-month-index'); // Get the month index here

                // Make an AJAX request to get employee data
                $.ajax({
                    url: '<?php echo e(route('manning.fetch.employees')); ?>',
                    type: 'POST',
                    data: {
                        position_id: positionId,
                        count: input.value
                    },
                    success: function(response) {
                        let monthCell = document.querySelector(`#${monthName}-${positionId}`);
                        filledCount = 0;
                        vacantCount = 0;

                        if (Array.isArray(response) && response.length > 0) {
                            response.forEach(employee => {
                                if (employee.name === "Vacant") {
                                    vacantCount++;
                                } else {
                                    filledCount++;
                                }
                            });
                            let employeeNames = response.map((employee, index) => {
                                if (employee.name === "Vacant") {
                                    return '<div><span class="badge bg-vac">Vacant</span></div>';
                                }
                                return `<div>${index + 1}. ${employee.name}</div>`;
                            }).join('');

                            monthCell.innerHTML = employeeNames;
                        } else {
                            monthCell.innerHTML = '<div><span class="badge bg-vac">Vacant</span></div>';
                        }

                        $(`#collapse-${positionId}`).collapse('show');
                        let filledInput = document.querySelector(`#filled_positions_${positionId}_${monthIndex}`);
                        let vacantInput = document.querySelector(`#vacant_positions_${positionId}_${monthIndex}`);

                        if (filledInput) {
                            filledInput.value = filledCount;
                        } else {
                            console.error(`Filled positions input not found for position ID: ${positionId} and month index: ${monthIndex}`);
                        }

                        if (vacantInput) {
                            vacantInput.value = vacantCount;
                        } else {
                            console.error(`Vacant positions input not found for position ID: ${positionId}`);
                        }
                        event.stopPropagation();
                        updateHeadcountForMonth(positionId, monthName, parseInt(input.value),filledCount,vacantCount);
                    },
                    error: function(xhr, status, error) {
                        console.error('Error fetching employees:', error);
                    }
                });
            }

            updateMinusButtonState(input);
            event.stopPropagation();
        }

        function updateHeadcountForMonth(positionId, monthName, newCount, filledCount, vacantCount) {
            // console.log(positionId, monthName, newCount);

            if (!headcounts[positionId]) {
                headcounts[positionId] = { employees: {}, months: {} }; // Ensure employees and months properties exist
            }

            // Update the headcount for the specific month
            headcounts[positionId].months[monthName] = newCount;
            // console.log(headcounts[positionId].months[monthName]);

            // Ensure filled and vacant counts are stored per month
            headcounts[positionId].filledCounts = headcounts[positionId].filledCounts || {};
            headcounts[positionId].vacantCounts = headcounts[positionId].vacantCounts || {};
            headcounts[positionId].filledCounts[monthName] = filledCount;
            headcounts[positionId].vacantCounts[monthName] = vacantCount;

            // Ensure counts are valid numbers
            if (isNaN(filledCount) || isNaN(vacantCount)) {
                console.error(`Invalid filledCount or vacantCount: filledCount=${filledCount}, vacantCount=${vacantCount}`);
                console.error(`Headcount data for position ID ${positionId}:`, headcounts[positionId]);
            } else {
                // Update total counts for all months
                const totalFilledCount = calculateTotalCount(headcounts[positionId].filledCounts);
                const totalVacantCount = calculateTotalCount(headcounts[positionId].vacantCounts);

                // Get the max headcount for all months for this position
                const totalHeadcountForPosition = Math.max(...Object.values(headcounts[positionId].months)) || 0;

                // Get the max filled count for all months for this position
                const totalFilledCountForPosition = Math.max(...Object.values(headcounts[positionId].filledCounts)) || 0;
                const totalVacantCountForPosition = Math.max(...Object.values(headcounts[positionId].vacantCounts)) || 0;

                // console.log(totalFilledCountForPosition, "Max Vacant count for position");

                // Update the total headcount display for the position
                updateHeadcountDisplay(positionId, totalHeadcountForPosition, totalFilledCountForPosition, totalVacantCountForPosition);
            }
        }

        function calculateTotalCount(counts) {
            return Object.values(counts).reduce((total, count) => total + count, 0);
        }

        function updateHeadcountDisplay(positionId, totalHeadcount, totalFilledCount, totalVacantCount) {
            let headcountElement = document.querySelector(`#collapse-${positionId} .badge-headcount`);

            if (headcountElement) {
                const currentYear = new Date().getFullYear(); // e.g., 2024
                const nextYear = currentYear + 1; // e.g., 2025

                // Update the headcount display
                headcountElement.innerHTML = `
                    ${currentYear} HEADCOUNT = <br/>
                    ${nextYear} HEADCOUNT = ${totalHeadcount} <br/>
                    ${nextYear} Filled COUNT = ${totalFilledCount} <br/>
                    ${nextYear} Vacant COUNT = ${totalVacantCount}
                `;
            }

            // Update the total headcount across all positions
            updateTotalHeadcount();
        }

        function updateTotalHeadcount() {
            let totalHeadcount = 0;
            let totalFilledCount = 0;
            let totalVacantCount = 0;

            // Loop through each position in the headcounts object
            for (let positionId in headcounts) {
                if (headcounts.hasOwnProperty(positionId)) {
                    // Get the max headcount for all months for this position
                    let totalHeadcountForPosition = Math.max(...Object.values(headcounts[positionId].months)) || 0;

                    // Add to the overall total headcount
                    totalHeadcount += totalHeadcountForPosition;

                    // Get the max filled count for this position
                    let maxFilledCountForPosition = Math.max(...Object.values(headcounts[positionId].filledCounts)) || 0;

                    // Get the max vacant count for this position
                    let maxVacantCountForPosition = Math.max(...Object.values(headcounts[positionId].vacantCounts)) || 0;

                    // Ensure counts are valid numbers
                    if (isNaN(maxFilledCountForPosition) || isNaN(maxVacantCountForPosition)) {
                        console.error(`Invalid filledCount or vacantCount for position ID: ${positionId}. filledCount=${maxFilledCountForPosition}, vacantCount=${maxVacantCountForPosition}`);
                        console.error(`Headcount data for position ID ${positionId}:`, headcounts[positionId]);
                    } else {
                        // Add the max filled and vacant counts to the overall totals
                        totalFilledCount += maxFilledCountForPosition;
                        totalVacantCount += maxVacantCountForPosition;
                    }
                }
            }

            // Update the DOM elements for displaying the totals
            document.getElementById('total-headcount').textContent = totalHeadcount;
            $('#total_headcount').val(totalHeadcount); // Set hidden input value if needed
            $('#total_vacant_headcount').val(totalVacantCount);
            $('#total_filled_headcount').val(totalFilledCount);
            document.getElementById('overall_filled_positions').textContent = totalFilledCount;
            document.getElementById('overall_vacant_positions').textContent = totalVacantCount;
        }

        //Function to update the minus button state
        function updateMinusButtonState(input) {
            let currentValue = parseInt(input.value);
            let decrementButton = input.closest('.inputCounter-group').querySelector('[data-type="minus"]');
            if (currentValue <= 1) {
                decrementButton.setAttribute('disabled', true);
            } else {
                decrementButton.removeAttribute('disabled');
            }
        }

        function handleInputChange(input) {
            let currentValue = parseInt(input.value);
            let maxValue = parseInt(input.getAttribute('max'));

            // Validate input value
            if (isNaN(currentValue) || currentValue < 0) {
                input.value = 0;
                currentValue = 0;
            } else if (currentValue > maxValue) {
                input.value = maxValue;
                currentValue = maxValue;
            }

            let positionId = input.getAttribute('data-position-id');
            let monthName = input.getAttribute('data-month').toLowerCase();
            let monthIndex = input.getAttribute('data-month-index');

            $.ajax({
                url: '<?php echo e(route('manning.fetch.employees')); ?>',
                type: 'POST',
                data: {
                    position_id: positionId,
                    count: currentValue
                },
                success: function(response) {
                    let monthCell = document.querySelector(`#${monthName}-${positionId}`);
                    let filledCount = 0;
                    let vacantCount = 0;

                    if (Array.isArray(response) && response.length > 0) {
                        response.forEach(employee => {

                            console.log(employee.name);
                            if (employee.name === "Vacant") {
                                vacantCount++;
                            } else {
                                filledCount++;
                            }
                        });

                        monthCell.innerHTML = response.map((employee, index) =>
                            employee.name === "Vacant" ? '<div><span class="badge bg-vac">Vacant</span></div>' : `<div>${index + 1}. ${employee.name}</div>`
                        ).join('');
                    } else {
                        // monthCell.innerHTML = '<div><span class="badge bg-vac">Vacant</span></div>';
                        monthCell.innerHTML = '';
                        vacantCount = currentValue; // Assume all positions are vacant
                    }

                    // Update hidden fields
                    let filledInput = document.querySelector(`#filled_positions_${positionId}_${monthIndex}`);
                    let vacantInput = document.querySelector(`#vacant_positions_${positionId}_${monthIndex}`);

                    if (filledInput) filledInput.value = filledCount;
                    if (vacantInput) vacantInput.value = vacantCount;

                    updateHeadcountForMonth(positionId, monthName, currentValue, filledCount, vacantCount);
                },
                error: function(xhr, status, error) {
                    console.error('Error fetching employees:', error);
                }
            });

            updateMinusButtonState(input);
        }

        // Add event listener to all input fields to handle direct changes
        $('.input-number').on('change', function() {
            handleInputChange(this);
        });

        // Make sure to initialize the state of minus buttons on page load
        document.querySelectorAll('.input-number').forEach(input => {
            updateMinusButtonState(input); // Call this for each input to set initial state
        });

        // Event listener for input change
        document.querySelectorAll('.input-number').forEach(input => {
            input.addEventListener('change', function() {
                handleInputChange(this);
            });
        });

        // Event listener for the checkbox
        document.getElementById('flexCheckChecked').addEventListener('change', function() {
            if (this.checked) {
                let deptID = $('#dept_id').val();
                let resort_id = $('#resort_id').val();

                $.ajax({
                    url: '<?php echo e(route('manning.fetch.currentYearData')); ?>',
                    type: 'POST',
                    data: {
                        dept_id: deptID,
                        resort_id: resort_id,
                        _token: '<?php echo e(csrf_token()); ?>'
                    },
                    success: function(response) {
                        if (response) {
                            for (let positionId in response) {
                                if (response.hasOwnProperty(positionId)) {
                                    for (let month = 1; month <= 12; month++) {
                                        let monthData = response[positionId][month];
                                        if (monthData) {
                                            let input = $(`#count-${positionId}-${month - 1}`);
                                            if (input.length) {
                                                input.val(monthData.headcount || 0);
                                                handleInputChange(input[0]); // Call with DOM element
                                            }
                                        }
                                    }
                                }
                            }
                            updateTotalHeadcount(); // Update the total headcount display
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error fetching current year data:', error);
                    }
                });
            } else {
                // Optionally reset the input values when the checkbox is unchecked
                $('.input-number').val(0); // Resetting all input numbers
                updateTotalHeadcount(); // Update total headcount display after reset
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            // Save As Draft button click event
            document.getElementById('saveDraftBtn').addEventListener('click', function(e) {
                e.preventDefault();

                // Serialize the form data
                let formData = new FormData(document.getElementById('manningResponseForm'));

                // Append 'draft' status
                formData.append('status', 'draft');

                // Send the form data via AJAX to save as draft
                saveAsDraft(formData);
            });

            // Function to handle AJAX request
            function saveAsDraft(formData) {
                // Make an AJAX request to save the draft
                fetch("<?php echo e(route('manning.responses.saveDraft')); ?>", {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Draft saved successfully!');
                        // Optionally, you can close the modal or provide more feedback
                    } else {
                        alert('Error saving draft. Please try again.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An unexpected error occurred.');
                });
            }
        });

        function fetchDraftData(resort_id, Dept_id, year) {
            // console.log(resort_id, Dept_id, year);
            $.ajax({
                // Use JavaScript string interpolation to pass the dynamic values in the URL
                url: `<?php echo e(route('manning.responses.getDraft', ['resortId' => ':resort_id', 'deptId' => ':Dept_id', 'year' => ':year'])); ?>`
                    .replace(':resort_id', resort_id)
                    .replace(':Dept_id', Dept_id)
                    .replace(':year', year),
                type: 'GET',
                success: function(response) {
                    // console.log("AJAX Response:", response); // Log the response regardless of success or failure

                    if (response) {
                        // Handle successful response
                        for (let positionId in response) {
                            // console.log(positionId);
                            if (response.hasOwnProperty(positionId)) {
                                for (let month = 1; month <= 12; month++) {
                                    let monthData = response[positionId][month];
                                    // console.log(monthData, "monthData");
                                    if (monthData) {
                                        let input = $(`#count-${positionId}-${month - 1}`);
                                        // console.log(input, "INPUT");
                                        if (input.length) {
                                            input.val(monthData.headcount || 0);

                                            handleInputChange(input[0]);
                                        }
                                    }
                                }
                            }
                        }
                        updateTotalHeadcount(); // Update the total headcount display
                    } else {
                        // console.warn("Response did not indicate success:", response.msg);
                        toastr.warning(response.msg, "Warning", {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    // console.error("AJAX Error: Status -", status, "Error -", error);
                    // console.error("Response:", xhr.responseText); // Log the response text for more details
                    toastr.error("Error fetching draft data.", "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                }
            });
        }

    </script>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('resorts.layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\wamp64\www\Wisdom-Ai\HRVMS-Wisdom-AI\resources\views/resorts/workforce_planning/hoddashboard.blade.php ENDPATH**/ ?>