<?php
     $currentRoute = $page_route;
?>
<ul class="nav flex-column">
     <?php $__currentLoopData = $menu['menu']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s => $ak): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <?php if(!isset($ak['submenu']) || empty($ak['submenu'])): ?>
               <?php continue; ?>
          <?php endif; ?>
          <?php
               $isActiveModule = collect($ak['submenu'])->pluck('route')->contains($currentRoute);
          ?>
          <li class="dropdown-submenu">
               <a class="dropdown-item dropdown-toggle <?php echo e($isActiveModule ? 'activeModule' : ''); ?>" href="javascript:void()"><?php echo e($ak['ModuleName']); ?></a>
               <ul class="dropdown-menu">
                    <?php $__currentLoopData = $ak['submenu']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sm): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                         <li><a class="dropdown-item <?php if($currentRoute == $sm['route']): ?> activeModule <?php endif; ?>" href="<?php echo e(route($sm['route'])); ?>"><?php echo e($sm['PageName']); ?></a></li>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
               </ul>
          </li>
     <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</ul><?php /**PATH C:\wamp64\www\Wisdom-Ai\HRVMS-Wisdom-AI\resources\views/resorts/layouts/vertical-menu.blade.php ENDPATH**/ ?>