<?php if($EmployeeLeave->isNotEmpty()): ?>


<div class="createDuty-user mb-md-4 mb-3">
    <div class="img-circle"><img src=" <?php echo e(Common::getResortUserPicture($employees->Parentid)); ?>" alt="user">
    </div>
    <div>
        <p><span class="fw-600"><?php echo e(ucfirst($employees->first_name .'  '.$employees->last_name)); ?></span> <span
                class="badge badge-themeLight"><?php echo e($employees->Emp_id); ?> </span></p>
        <span><?php echo e(ucfirst($employees->position_title)); ?></span>
    </div>
</div>

<div class="card-themeSkyblue">
    <?php $__currentLoopData = $EmployeeLeave; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $leave): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>


    <p class="fw-600 mb-1">Planned Leaves</p>
    <div class="d-flex align-items-center mb-3">
        <div class="bg-white">
            <?php echo e(date("M", strtotime($leave->from_date))); ?>


            <h5><?php echo e(date("d", strtotime($leave->from_date))); ?></h5> <?php echo e(date("D", strtotime($leave->from_date))); ?>

        </div>
        <div>
            <span class="badge badge-brown mb-2"><?php echo e($leave->leave_type); ?></span>
            <p>  <?php echo e($leave->from_date == $leave->to_date ? date("d/m/Y", strtotime($leave->from_date)) : date("d/m/Y", strtotime($leave->from_date)) .' to '.date("d/m/Y", strtotime($leave->to_date))); ?>   <?php echo e($leave->reason); ?></p>
        </div>
    </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>


</div>
<?php else: ?>
<div class="card-themeSkyblue">
<p>No Leave Applied</p>
</div>

<?php endif; ?>
<?php /**PATH C:\wamp64\www\Wisdom-Ai\HRVMS-Wisdom-AI\resources\views/resorts/renderfiles/dutyrosterandLeave.blade.php ENDPATH**/ ?>