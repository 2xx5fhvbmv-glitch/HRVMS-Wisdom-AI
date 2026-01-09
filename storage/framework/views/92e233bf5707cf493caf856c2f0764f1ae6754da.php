
<?php if($resort_divisions): ?>
    <?php  $total = 0;
    ?>

    <div class="total-b-box text-center mb-2">
        <p class="fs-18 fw-500">Total Budget: <span class="text-theme TotalBudget "><img class="currency-budget-icon h-18" src="<?php echo e($currency); ?>"><?php echo e(number_format($total, 2)); ?></span></p>
    </div>
    <div class="accordion budget-accordion" id="accordionExample">
        <?php $__currentLoopData = $resort_divisions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div class="accordion-item">
            <h2 class="accordion-header" id="heading<?php echo e($key); ?>">
                <button class="accordion-button <?php echo e($key == 0 ? '' : 'collapsed'); ?>"


                type="button" data-bs-toggle="collapse"
                    data-bs-target="#collapse<?php echo e($key); ?>" aria-expanded="<?php echo e($key == 0 ? 'true' : 'false'); ?>"
                    aria-controls="collapse<?php echo e($key); ?>">
                    <div class="d-flex align-items-center justify-content-between w-100 pe-sm-4 pe-1">
                        <span class="name"> <?php echo e($value->name); ?></span>

                        <?php

                            $departmentTotal =  array_key_exists($value->id, $departmenet_total) ? $departmenet_total[$value->id] : 0.00;
                            $total += $departmentTotal;
                        ?>
                        <span class="lable-budget">Budget:<?php echo number_format($departmentTotal,2); ?>  </span>

                    </div>
                </button>
            </h2>
            <div id="collapse<?php echo e($key); ?>" class="accordion-collapse collapse <?php echo e($key == 0 ? 'show' : ''); ?>"
                aria-labelledby="heading<?php echo e($key); ?>" data-bs-parent="#accordionExample">
                <div class="accordion-body">
                    <?php $__currentLoopData = $resort_departments->where('division_id', $value->id); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $department): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php

                            $array = [];
                            if(!empty($department->monthWiseBudget)) 
                            {
                                
                                foreach ($department->monthWiseBudget as $Key => $item) {
                                    $array['dept_id'] = $item['dept_id'];

                                    $array['position_monthly_data_id'][] = $item['id'];
                                    $array['manning_response_id'] = $item['manning_response_id'];
                                    $array['Message_id'] = $department->Message_id;
                                }
                            }
                        ?>
                        <div class="accordion-innerbox bg-white d-flex justify-content-between align-items-center">
                            <?php if($department->BudgetPageLink == 1): ?>
                                <form id="departmentBudget" method="POST" action="<?php echo e(route('resort.department.wise.budget.data')); ?>">
                                    <?php echo csrf_field(); ?>

                                <a href="javascript::void(0)" target="_blank">
                                        <p class="mb-0 fw-500 departmentBudget">
                                            <input type="hidden" name="data[]" value="<?php echo e(json_encode($array)); ?>">
                                            <?php echo e($department->name); ?>


                                            <span class="badge ms-sm-3 badge-warning"><?php echo e($department->BudgetStatus); ?>  </span>
                                        </p>
                                    </a>


                                    <button type="submit"   <?php if($department->BudgetPageLink == 1): ?> class="submitBtn"  <?php endif; ?> style="display: none;">Submit</button>


                                </form>
                            <?php else: ?>
                                <p class="mb-0 fw-500 departmentBudget">
                                    <?php echo e($department->name); ?>

                                    <span class="badge ms-sm-3 badge-warning"><?php echo e($department->BudgetStatus); ?></span>
                                </p>
                            <?php endif; ?>
                            <span class="fw-normal">Budget:      <img class="currency-budget-icon" src="<?php echo e($currency); ?>"><?php echo e(isset($department->OldEmployeesBudgetValue )  ? $department->OldEmployeesBudgetValue : 0.00); ?></span>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                </div>
            </div>
        </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
<?php endif; ?>


    </div>

    <script>
        document.querySelector('.TotalBudget').innerHTML = ' <img class="currency-budget-icon h-18" src="<?php echo e($currency); ?>">  <?php echo e(number_format($total, 2)); ?>';
    </script>
<?php /**PATH C:\wamp64\www\Wisdom-Ai\HRVMS-Wisdom-AI\resources\views/resorts/renderfiles/MonthWiseManningBudget.blade.php ENDPATH**/ ?>