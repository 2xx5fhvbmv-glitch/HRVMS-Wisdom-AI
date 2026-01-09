
<?php if(!empty($ModuleWisePermission)): ?>

<?php $__currentLoopData = $ModuleWisePermission; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $moduleId => $data): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <?php
        // Use a loop counter to make the first item open by default
        static $isFirst = true;
    ?>
    <div class="accordion-item">
        <h2 class="accordion-header" id="headingOne<?php echo e($moduleId); ?>">
            <button class="accordion-button <?php echo e($isFirst ? '' : 'collapsed'); ?>"  type="button" data-bs-toggle="collapse"  data-bs-target="#collapseOne<?php echo e($moduleId); ?>" aria-expanded="true" aria-controls="collapseOne">

                <?php if(isset($data['module']->module_name)): ?>
                <?php echo e($data['module']->module_name); ?> 
                
                <?php else: ?>
                Module not found
                <?php endif; ?>
            </button>
        </h2>
        <div id="collapseOne<?php echo e($moduleId); ?>" class="accordion-collapse collapse  <?php echo e($isFirst ? 'show' : ''); ?>" aria-labelledby="headingOne<?php echo e($moduleId); ?>" data-bs-parent="#accordionPermissions">
            <div class="table-responsive">
                <?php if(isset($data['permissions'])): ?>

                    <table class="table">
                        <thead>
                            <tr>
                                <th>Page Name</th>
                                <th>Select All</th>
                                <th>View</th>
                                <th>Create</th>
                                <th>Edit</th>
                                <th>Delete</th>
                                
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__currentLoopData = $data['permissions']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $permission): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            
                            <tr>
                                <td>
                                    <?php if(isset($permission['module_page'])): ?>
                                        <?php echo e($permission['module_page']->page_name); ?> 
                                    <?php else: ?>
                                        Page Not found Please Contact To Super Admin
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="form-check">

                                        <input class="form-check-input Resort_parent_checkbox" data-id="<?php echo e($permission['module_page']->id); ?>_<?php echo e($moduleId); ?>" id="parent_<?php echo e($permission['module_page']->id); ?>_<?php echo e($moduleId); ?>" name="module_permissions_parent" type="checkbox" value=""
                                            <?php if(!empty($ModuleWiseExitingPermissions) && array_key_exists($permission['module_page']->id,$ModuleWiseExitingPermissions)): ?>;
                                            checked
                                            <?php endif; ?>>
                                    </div>

                                    <?php if(!empty(config('settings.resort_permissions'))): ?>
                                        <?php $__currentLoopData = config('settings.resort_permissions'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $internal): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <td>
                                            <div class="form-check">
                                                <input class="form-check-input  child_parent_<?php echo e($permission['module_page']->id); ?>_<?php echo e($moduleId); ?>" id="child_<?php echo e($permission['module_page']->id); ?>" name="Resort_page_permissions[<?php echo e($permission['module_page']->id); ?>][]" type="checkbox" value="<?php echo e($internal); ?>"
                                                    <?php if(!empty($ModuleWiseExitingPermissions) && array_key_exists($permission['module_page']->id,$ModuleWiseExitingPermissions) && in_array($internal,$ModuleWiseExitingPermissions[$permission['module_page']->id])): ?>;
                                                    checked

                                                    <?php endif; ?>
                                                >
                                              
                                            </div>
                                        </td>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    <?php endif; ?>
                            </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>


                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No permissions found for this module.</p>
                <?php endif; ?>

            </div>
        </div>
    </div>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
<?php endif; ?>
<?php /**PATH C:\wamp64\www\Wisdom-Ai\HRVMS-Wisdom-AI\resources\views/resorts/renderfiles/ResortPermissionRender.blade.php ENDPATH**/ ?>