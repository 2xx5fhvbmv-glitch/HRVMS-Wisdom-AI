<?php
    $currentRoute = $page_route;
?>
<div class="carosel-menu">
    <?php $__currentLoopData = $menu['menu']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ak): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php if(!isset($ak['submenu']) || empty($ak['submenu'])): ?>
            <?php continue; ?>
        <?php endif; ?>

            <?php
                $isActiveModule = collect($ak['submenu'])->pluck('route')->contains($currentRoute);
            ?>
            <div class="text-center" id="caroselMenuActive">

                <div class="btn-group">
                    <a type="button" class="dropdown-toggle <?php echo e($isActiveModule ? 'active' : ''); ?>"  data-bs-toggle="dropdown"
                        aria-expanded="false">
                        <?php echo e($ak['ModuleName']); ?>

                    </a>
                    <div class="dropdown-menu carosel-nav-menu">
                        <ul class="nav flex-column">
                            <?php $__currentLoopData = $ak['submenu']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sm): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <li>
                                    <a class="dropdown-item <?php if($currentRoute == $sm['route']): ?> active-Route <?php endif; ?>" href="<?php echo e(route($sm['route'])); ?>">
                                        <?php echo e($sm['PageName']); ?>

                                    </a>
                                </li>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>             
                        </ul>
                    </div>
                </div>
            </div>
    
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div><?php /**PATH C:\wamp64\www\Wisdom-Ai\HRVMS-Wisdom-AI\resources\views/resorts/layouts/desktop-menu.blade.php ENDPATH**/ ?>