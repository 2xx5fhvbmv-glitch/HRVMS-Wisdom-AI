<div class="viewBudget-accordion" id="accordionViewBudget">
    <?php if(!empty($consolidatedBudget)): ?>
        <?php $divisionIteration = 1; ?>
        <?php $__currentLoopData = $consolidatedBudget; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $divisionName => $divisionData): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            
            <div class="accordion-item mb-2 division-accordion">
                <h2 class="accordion-header" id="headingDiv<?php echo e($divisionIteration); ?>">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                            data-bs-target="#collapseDiv<?php echo e($divisionIteration); ?>" aria-expanded="false"
                            aria-controls="collapseDiv<?php echo e($divisionIteration); ?>">
                        <i class="fas fa-building me-2"></i>
                        <h3><?php echo e($divisionName); ?></h3>
                        <span class="badge badge-dark ms-2 small divisionGrandTotal">Budget: $<?php echo e(number_format($divisionData['calculated_total'] ?? 0, 2)); ?></span>
                    </button>
                </h2>
                <div id="collapseDiv<?php echo e($divisionIteration); ?>" class="collapse"
                     aria-labelledby="headingDiv<?php echo e($divisionIteration); ?>" data-bs-parent="#accordionViewBudget">
                    <div class="accordion-body p-2">
                        <?php $deptIteration = 1; ?>
                        <?php $__currentLoopData = $divisionData['departments']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $departmentName => $departmentData): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            
                            <div class="accordion mb-2 ms-3 department-accordion" id="accordionDept<?php echo e($divisionIteration); ?>_<?php echo e($deptIteration); ?>">
                                <div class="accordion-item">
                                    <div class="row g-0 align-items-center">
                                        <div class="col-md-9">
                                            <h2 class="accordion-header" id="headingDept<?php echo e($divisionIteration); ?>_<?php echo e($deptIteration); ?>">
                                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                                        data-bs-target="#collapseDept<?php echo e($divisionIteration); ?>_<?php echo e($deptIteration); ?>"
                                                        aria-expanded="false" aria-controls="collapseDept<?php echo e($divisionIteration); ?>_<?php echo e($deptIteration); ?>">
                                                    <i class="fas fa-sitemap me-2"></i>
                                                    <span><?php echo e($departmentName); ?></span>
                                                    <span class="badge badge-dark ms-2 small departmentGrandTotal">Budget: $<?php echo e(number_format($departmentData['calculated_total'] ?? 0, 2)); ?></span>
                                                </button>
                                            </h2>
                                        </div>
                                        <div class="col-md-3 text-end pe-2">
                                            <?php if($employeeRankPosition['position'] == 'HR' || $employeeRankPosition['position'] == 'Finance'): ?>
                                            <a href="#revise-budgetmodal"
                                               data-budget_id="<?php echo e($departmentData['manning_response_id']); ?>"
                                               data-dept_id="<?php echo e($departmentData['department_id']); ?>"
                                               data-bs-toggle="modal"
                                               class="btn btn-sm btn-white open-revise-modal">
                                                <span class="badge badge-danger">
                                                    <i class="fa-solid fa-clock-rotate-left"></i> Revise Budget
                                                </span>
                                            </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div id="collapseDept<?php echo e($divisionIteration); ?>_<?php echo e($deptIteration); ?>"
                                         class="collapse"
                                         aria-labelledby="headingDept<?php echo e($divisionIteration); ?>_<?php echo e($deptIteration); ?>"
                                         data-bs-parent="#accordionDept<?php echo e($divisionIteration); ?>_<?php echo e($deptIteration); ?>">
                                        <div class="accordion-body p-2">

                                            
                                            <?php if(!empty($departmentData['sections'])): ?>
                                                <?php $sectionIteration = 1; ?>
                                                <?php $__currentLoopData = $departmentData['sections']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sectionName => $sectionData): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                    <div class="accordion mb-2 ms-3 section-accordion" id="accordionSec<?php echo e($divisionIteration); ?>_<?php echo e($deptIteration); ?>_<?php echo e($sectionIteration); ?>">
                                                        <div class="accordion-item">
                                                            <h2 class="accordion-header" id="headingSec<?php echo e($divisionIteration); ?>_<?php echo e($deptIteration); ?>_<?php echo e($sectionIteration); ?>">
                                                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                                                        data-bs-target="#collapseSec<?php echo e($divisionIteration); ?>_<?php echo e($deptIteration); ?>_<?php echo e($sectionIteration); ?>"
                                                                        aria-expanded="false" aria-controls="collapseSec<?php echo e($divisionIteration); ?>_<?php echo e($deptIteration); ?>_<?php echo e($sectionIteration); ?>">
                                                                    <i class="fas fa-layer-group me-2"></i>
                                                                    <span><?php echo e($sectionName); ?></span>
                                                                    <span class="badge badge-dark ms-2 small sectionGrandTotal">Budget: $<?php echo e(number_format($sectionData['calculated_total'] ?? 0, 2)); ?></span>
                                                                </button>
                                                            </h2>

                                                            <div id="collapseSec<?php echo e($divisionIteration); ?>_<?php echo e($deptIteration); ?>_<?php echo e($sectionIteration); ?>"
                                                                 class="collapse"
                                                                 aria-labelledby="headingSec<?php echo e($divisionIteration); ?>_<?php echo e($deptIteration); ?>_<?php echo e($sectionIteration); ?>"
                                                                 data-bs-parent="#accordionSec<?php echo e($divisionIteration); ?>_<?php echo e($deptIteration); ?>_<?php echo e($sectionIteration); ?>">
                                                                <div class="accordion-body p-2">
                                                                    <?php $posSecIteration = 1; ?>
                                                                    <?php $__currentLoopData = $sectionData['positions']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $positionName => $positionData): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                                        <div class="ms-3 mb-2">
                                                                            <?php echo $__env->make('resorts.renderfiles.position_tree_item', [
                                                                                'positionName' => $positionName,
                                                                                'positionData' => $positionData,
                                                                                'departmentData' => $departmentData,
                                                                                'departmentName' => $departmentName,
                                                                                'resortCosts' => $resortCosts,
                                                                                'header' => $header,
                                                                                'accordionId' => "pos{$divisionIteration}_{$deptIteration}_{$sectionIteration}_{$posSecIteration}"
                                                                            ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                                                                        </div>
                                                                        <?php $posSecIteration++; ?>
                                                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <?php $sectionIteration++; ?>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                            <?php endif; ?>

                                            
                                            <?php if(!empty($departmentData['positions'])): ?>
                                                <?php $positionIteration = 1; ?>
                                                <?php $__currentLoopData = $departmentData['positions']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $positionName => $positionData): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                    <div class="ms-3 mb-2">
                                                        <?php echo $__env->make('resorts.renderfiles.position_tree_item', [
                                                            'positionName' => $positionName,
                                                            'positionData' => $positionData,
                                                            'departmentData' => $departmentData,
                                                            'departmentName' => $departmentName,
                                                            'resortCosts' => $resortCosts,
                                                            'header' => $header,
                                                            'accordionId' => "pos{$divisionIteration}_{$deptIteration}_0_{$positionIteration}"
                                                        ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                                                    </div>
                                                    <?php $positionIteration++; ?>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                            <?php endif; ?>

                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php $deptIteration++; ?>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                </div>
            </div>
            <?php $divisionIteration++; ?>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    <?php endif; ?>
</div>
<?php /**PATH C:\wamp64\www\Wisdom-Ai\HRVMS-Wisdom-AI\resources\views/resorts/renderfiles/consolidated.blade.php ENDPATH**/ ?>