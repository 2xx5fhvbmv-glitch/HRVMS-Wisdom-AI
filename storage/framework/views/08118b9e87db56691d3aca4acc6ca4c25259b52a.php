<?php $__env->startSection('page_tab_title' ,$page_title); ?>

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
                        <h1><?php echo e($page_title); ?></h1>
                    </div>
                </div>
                <div class="col-auto">
                    <div class="d-flex justify-content-end">
                        <form id="SendToFinance" method="POST"  >
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="year" id="SendToFinanceYear" value="<?php echo e($year); ?>">
                            <p class="mb-0 fw-500 departmentBudget"></p>
                            <?php if($employeeRankPosition['position'] == 'HR'): ?>
                                <button type="submit" class="btn btn-theme SendToFinance" <?php echo e($isBudgetCompleted ? '' : 'disabled'); ?>>Send To Finance</button>
                            <?php endif; ?>
                            <?php if($employeeRankPosition['position'] == 'Finance'): ?>
                                <button type="submit" class="btn btn-theme SendToGM" <?php echo e($isBudgetCompleted ? '' : 'disabled'); ?>>Send To GM</button>
                            <?php endif; ?>
                            <?php if($employeeRankPosition['position'] == 'GM'): ?>
                                
                                <button type="submit" class="btn btn-theme SendToCorporateOffice" >Aproove Budget</button>

                                <a href="#revise-budgetmodal" 
                                    class="open-revise-modal btn btn-white ms-3"
                                    style="background: #F5F8F8;"
                                    data-budget_id="<?php echo e($deptData->Budget_id); ?>"
                                    data-dept_id="<?php echo e($deptData->department->id); ?>"
                                    data-bs-toggle="modal">
                                        <span class="badge badge-danger">
                                            <i class="fa-solid fa-clock-rotate-left"></i> Revise Budget
                                        </span>
                                </a>
                            <?php endif; ?>
                            
                        </form>
                    </div>
                </div>
            </div>
        </div>

    <div>

    <div class="card">
        <div class="row g-3 justify-content-end mb-4">
            <div class="col-auto">
                <div class="d-flex align-items-center justify-content-sm-end">
                    
                        <div class="form-group me-2">
                        <form method="GET" action="<?php echo e(route('resort.budget.manning')); ?>" id="yearFilterForm">
                            <select class="form-select ManningBudgetYearWise" id="yearFilter" name="year" 
                            onchange="document.getElementById('yearFilterForm').submit();">
                    
                        <?php
                            $currentYear = date('Y');
                            $startYear = $currentYear - 10;
                            $endYear = $currentYear + 1;
                    
                            // If no year in request, use current year
                            $selectedYear = request()->get('year', $currentYear);
                        ?>
                    
                        <?php for($loopyear = $startYear; $loopyear <= $endYear; $loopyear++): ?>
                            <option value="<?php echo e($loopyear); ?>" 
                                <?php echo e((int)$loopyear === (int)$selectedYear ? 'selected' : ''); ?>>
                                <?php echo e($loopyear); ?>

                            </option>
                        <?php endfor; ?>
                    </select>
                        </form>
                    </div>
                </div>
            </div>
            <!-- <div class="col-auto">
                <a href="#" class="btn btn-themeBlue btn-sm btn-allShow">All Department Show </a>
            </div>
            <div class="col-auto">
                <a href="#" class="btn  btn-themeBlue btn-sm btn-allHIde">All Department Hide </a>
            </div> -->
            <!-- <div class="col-auto">
                <div class="d-flex align-items-center">
                    <label for="flexSwitchCheckDefault" class="form-label mb-0 me-3">All Department
                        Show</label>
                    <div class="form-check form-switch form-switchTheme department-switch">
                        <input class="form-check-input" type="checkbox" role="switch"
                            id="flexSwitchCheckDefault">
                        <label class="form-check-label" for=""></label>
                    </div>
                </div>
            </div> -->
        </div>


        <div class="viewBudget-accordion" id="accordionViewBudget">
            <?php if($departmentsData): ?>
                <?php $__currentLoopData = $departmentsData; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $deptData): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="accordion-item">
                        <div class="row g-3 align-items-center">
                            <div class="col-9">
                                <h2 class="accordion-header" id="heading<?php echo e($key); ?>">
                                    <button class="accordion-button <?php echo e($key == 0 ? '' : 'collapsed'); ?>" type="button" data-bs-toggle="collapse"
                                        data-bs-target="#collapse<?php echo e($key); ?>" aria-expanded="<?php echo e($key == 0 ? 'true' : 'false'); ?>" aria-controls="collapse<?php echo e($key); ?>">
                                        <h3><?php echo e($deptData['department']->name); ?></h3>
                                    </button>
                                </h2>
                            </div>
                            <div class="col-3 justify-content-end d-flex" >
                                <?php if($employeeRankPosition['position'] == 'HR' || $employeeRankPosition['position'] == 'Finance'): ?>
                                    <a href="#revise-budgetmodal" 
                                        class="open-revise-modal btn btn-white ms-3"
                                        style="background: #F5F8F8;"
                                        data-budget_id="<?php echo e($deptData['Budget_id']); ?>"
                                        data-dept_id="<?php echo e($deptData['department']->id); ?>"
                                        data-bs-toggle="modal">
                                            <span class="badge badge-danger">
                                                <i class="fa-solid fa-clock-rotate-left"></i> Revise Budget
                                            </span>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        
                        <div id="collapse<?php echo e($key); ?>" class="accordion-collapse collapse <?php echo e($key == 0 ? 'show' : ''); ?>" aria-labelledby="heading<?php echo e($key); ?>"
                            data-bs-parent="#accordionViewBudget">
                            <div class="table-responsive">
                                <table class="table table-viewMannAccording  w-100">
                                    <thead>
                                        <tr>
                                            <th class="text-nowrap">Positions</th>
                                            <th class="text-nowrap">No. of position</th>
                                            <th class="text-nowrap">Employee Name</th>
                                            <th class="text-nowrap w-120">Rank</th>
                                            <th class="text-nowrap w-120">Nation</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if($deptData['positions']->isNotEmpty()): ?>
                                            <?php $__currentLoopData = $deptData['positions']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $pos): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <tr>
                                                    <td><?php echo e($pos->position_title); ?></td>
                                                    <td><?php echo e($pos->headcount ?? '00'); ?></td>
                                                    <td colspan="3" class="p-0">
                                                        <table class="table m-0">
                                                            <?php if($pos->employees && count($pos->employees) > 0): ?>
                                                                <?php $__currentLoopData = $pos->employees; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $employee): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                                    <tr>
                                                                        <td><?php echo e($employee->first_name); ?> <?php echo e($employee->last_name); ?></td>
                                                                        <td class="w-120">
                                                                            <?php $Rank = config( 'settings.Position_Rank');
                                                                                $AvilableRank = array_key_exists($employee->rank, $Rank) ? $Rank[$employee->rank] : '';
                                                                            ?>
                                                                            <?php echo e($AvilableRank); ?>

                                                                        </td>
                                                                        <td class="w-120"><?php echo e($employee->nationality); ?></td>
                                                                    </tr>
                                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                            <?php endif; ?>
                                                            <?php if($pos->vacantcount): ?>
                                                                <?php for($i=0; $i<$pos->vacantcount; $i++): ?>
                                                                    <tr>
                                                                        <td colspan="5"><span class="badge badge-success">Vacant
                                                                        </span></td>
                                                                    </tr>
                                                                <?php endfor; ?>
                                                            <?php endif; ?>
                                                        </table>
                                                    </td>
                                                </tr>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            <?php endif; ?>
        </div>
    </div>
        </div>
    </div>
</div>

<div class="modal fade" id="revise-budgetmodal" tabindex="-1" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-small">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="staticBackdropLabel">Revise Budget</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">

                <form id="ReviseBudget" method="POST">
                    <?php echo csrf_field(); ?>
                <div class="form-group mb-20">
                    <input type="hidden" name="budget_id" id="budget_id" value="">
                    <input type="hidden" name="department_id" id="department_id" value="">
                    <textarea class="form-control" name="ReviseBudgetComment" id="ReviseBudgetComment" rows="7" placeholder="Add Comment Regarding Revision"></textarea>
                </div>
            </div>
            <div class="modal-footer justify-content-end">
                    <button type="button" class="btn btn-sm btn-themeGray me-2" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-theme">Submit</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('import-css'); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('import-scripts'); ?>
<script>
      $(document).on('click', '.open-revise-modal', function () {
            let budgetId = $(this).data("budget_id");
            let deptId = $(this).data("dept_id");

            $("#budget_id").val(budgetId);
            $("#department_id").val(deptId);
        });
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('resorts.layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\wamp64\www\Wisdom-Ai\HRVMS-Wisdom-AI\resources\views/resorts/budget/manning.blade.php ENDPATH**/ ?>